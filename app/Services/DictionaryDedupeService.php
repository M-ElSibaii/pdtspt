<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Core deduplication logic for `propertiesdatadictionaries`, shared by both the
 * `pdts:dedupe-dictionary` Artisan command and the admin review UI.
 *
 * This is the single source of truth for: grouping by nameEn, version-variant
 * detection, description-conflict detection, locating the `properties` rows that
 * reference a dictionary row, the propertyId/GUID disagreement flag, the JSON
 * backup format, and the repoint-then-delete mutation.
 *
 * SCHEMA (verified against the live DB):
 *   propertiesdatadictionaries: Id (PK), GUID, nameEn, definitionEn, definitionPt,
 *     versionNumber, revisionNumber, ...
 *   properties: Id (PK), GUID (FK -> dict.GUID), propertyId (-> dict.Id),
 *     descriptionEn, descriptionPt, ...
 */
class DictionaryDedupeService
{
    public const DICT_TABLE  = 'propertiesdatadictionaries';
    public const PROP_TABLE  = 'properties';
    public const NAME_COL    = 'nameEn';            // grouping key
    public const DESC_COLS   = ['definitionEn', 'definitionPt']; // compared for conflicts
    public const DICT_ID     = 'Id';
    public const DICT_GUID   = 'GUID';
    public const PROP_PROPID = 'propertyId';        // properties -> dict Id
    public const PROP_GUID   = 'GUID';              // properties -> dict GUID

    /**
     * Verify the required columns exist. Returns an error message, or null if OK.
     */
    public function schemaError(): ?string
    {
        $dictCols = array_merge([self::NAME_COL, self::DICT_ID, self::DICT_GUID], self::DESC_COLS);
        foreach ($dictCols as $col) {
            if (!Schema::hasColumn(self::DICT_TABLE, $col)) {
                return "Column '{$col}' not found on " . self::DICT_TABLE . ".";
            }
        }
        foreach ([self::PROP_PROPID, self::PROP_GUID] as $col) {
            if (!Schema::hasColumn(self::PROP_TABLE, $col)) {
                return "Column '{$col}' not found on " . self::PROP_TABLE . ".";
            }
        }
        return null;
    }

    /**
     * All nameEn values that occur more than once, mapped name => count.
     */
    public function duplicateNames(): Collection
    {
        return DB::table(self::DICT_TABLE)
            ->select(self::NAME_COL, DB::raw('COUNT(*) as cnt'))
            ->whereNotNull(self::NAME_COL)
            ->where(self::NAME_COL, '!=', '')
            ->groupBy(self::NAME_COL)
            ->having('cnt', '>', 1)
            ->pluck('cnt', self::NAME_COL);
    }

    /**
     * Analyze every duplicate nameEn group.
     *
     * @return array<int,array> list of group structures (see analyzeGroup()).
     */
    public function analyzeGroups(): array
    {
        return $this->duplicateNames()
            ->keys()
            ->map(fn($name) => $this->analyzeGroup((string) $name))
            ->filter()
            ->values()
            ->all();
    }

    /**
     * Analyze a single nameEn group. Returns null if it is no longer a duplicate
     * group (fewer than two rows share the name).
     *
     * Group structure:
     *   name, survivor (enriched row), duplicates[] (enriched rows, true duplicates),
     *   versionVariants[] (enriched, read-only), hasDescriptionConflict (bool),
     *   isActionable (bool), affectedCount (int), actionableIds[] (sorted int Ids),
     *   _survivorRow / _duplicateRows (raw DB objects, used by the command).
     */
    public function analyzeGroup(string $name): ?array
    {
        $rows = DB::table(self::DICT_TABLE)
            ->where(self::NAME_COL, $name)
            ->orderBy(self::DICT_ID, 'asc')
            ->get();

        if ($rows->count() < 2) {
            return null;
        }

        $survivor   = $rows->first();
        $duplicates = collect();   // true name duplicates (mergeable)
        $versions   = collect();   // version variants (left untouched)

        foreach ($rows->slice(1) as $row) {
            if ($this->isVersionVariant($survivor, $row)) {
                $versions->push($row);
            } else {
                $duplicates->push($row);
            }
        }
        $duplicates = $duplicates->values();
        $versions   = $versions->values();

        $hasConflict = $duplicates->contains(fn($d) => $this->descriptionsConflict($survivor, $d));

        $actionableIds = collect([$survivor])->merge($duplicates)
            ->pluck(self::DICT_ID)
            ->map(fn($i) => (int) $i)
            ->sort()
            ->values()
            ->all();

        return [
            'name'                   => $name,
            'survivor'               => $this->enrichRow($survivor),
            'duplicates'             => $duplicates->map(fn($d) => $this->enrichRow($d))->all(),
            'versionVariants'        => $versions->map(fn($v) => $this->enrichRow($v))->all(),
            'hasDescriptionConflict' => $hasConflict,
            'isActionable'           => $duplicates->isNotEmpty(),
            'affectedCount'          => $this->countReferencing($duplicates),
            'actionableIds'          => $actionableIds,
            '_survivorRow'           => $survivor,
            '_duplicateRows'         => $duplicates,
        ];
    }

    /**
     * Apply ONE group's resolution. Re-runs the analysis server-side and rejects
     * if the actionable Id set changed since the page was rendered. Transactional;
     * writes a JSON backup before mutating.
     *
     * Decision:
     *   action: 'merge' | 'keep_separate' | 'skip'
     *   name: string                       (group key)
     *   expectedActionableIds: int[]        (re-validated)
     *   merge: survivorId, definitionEn{mode,value}, definitionPt{mode,value}, acknowledgeMismatch(bool)
     *   keep_separate: renames{ dictId: newNameEn }
     */
    public function applyDecision(array $decision): array
    {
        $name   = isset($decision['name']) ? (string) $decision['name'] : '';
        $action = $decision['action'] ?? null;

        if ($name === '') {
            throw new \InvalidArgumentException('Missing group name.');
        }

        $group = $this->analyzeGroup($name);
        if (!$group) {
            throw new \RuntimeException("Group '{$name}' is no longer a duplicate group. The data changed — please reload.");
        }

        // Re-validate: the actionable Id set must be exactly what the page saw.
        $expected = collect($decision['expectedActionableIds'] ?? [])
            ->map(fn($i) => (int) $i)->sort()->values()->all();
        if ($expected !== $group['actionableIds']) {
            throw new \RuntimeException('This group changed since you loaded the page (rows were added or removed). Reload and try again.');
        }

        switch ($action) {
            case 'skip':
                return ['action' => 'skip', 'name' => $name, 'message' => 'Skipped — no changes made.'];
            case 'keep_separate':
                return $this->applyKeepSeparate($decision, $group);
            case 'merge':
                return $this->applyMerge($decision, $group);
            default:
                throw new \InvalidArgumentException("Unknown action '" . (string) $action . "'.");
        }
    }

    /**
     * MERGE: repoint every referencing properties row (propertyId + GUID) to the
     * chosen survivor and delete the other dictionary rows. Optionally overwrites
     * the survivor's definitionEn/definitionPt. Never touches properties.descriptionPt
     * and never touches version variants.
     */
    private function applyMerge(array $decision, array $group): array
    {
        $survivorId = (int) ($decision['survivorId'] ?? 0);
        if (!in_array($survivorId, $group['actionableIds'], true)) {
            throw new \RuntimeException('The chosen survivor is not part of this group.');
        }

        // Require explicit acknowledgement when any referencing row's propertyId/GUID disagree.
        $hasMismatch = collect(array_merge([$group['survivor']], $group['duplicates']))
            ->contains(fn($r) => !empty($r['mismatchProperties']));
        if ($hasMismatch && empty($decision['acknowledgeMismatch'])) {
            throw new \RuntimeException('Some referencing properties have a propertyId/GUID disagreement. You must acknowledge this before merging.');
        }

        // Fresh raw rows for the actionable set.
        $rows = DB::table(self::DICT_TABLE)
            ->whereIn(self::DICT_ID, $group['actionableIds'])
            ->get()
            ->keyBy(self::DICT_ID);

        $survivor   = $rows->get($survivorId);
        $duplicates = $rows->except($survivorId)->values();

        if (!$survivor) {
            throw new \RuntimeException('Survivor row vanished — please reload.');
        }

        $definitionEn = $this->resolveDefinition($decision['definitionEn'] ?? null, $survivor->definitionEn);
        $definitionPt = $this->resolveDefinition($decision['definitionPt'] ?? null, $survivor->definitionPt);

        $backupPath = $this->writeMergeBackup([[
            'name'       => $group['name'],
            'survivor'   => $survivor,
            'duplicates' => $duplicates,
        ]]);

        $repointed = 0;
        DB::transaction(function () use ($survivor, $duplicates, $definitionEn, $definitionPt, &$repointed) {
            $repointed = $this->repointDuplicatesToSurvivor($survivor, $duplicates);

            DB::table(self::DICT_TABLE)
                ->where(self::DICT_ID, $survivor->{self::DICT_ID})
                ->update([
                    'definitionEn' => $definitionEn,
                    'definitionPt' => $definitionPt,
                ]);
        });

        return [
            'action'     => 'merge',
            'name'        => $group['name'],
            'survivorId'  => $survivorId,
            'deleted'     => $duplicates->count(),
            'repointed'   => $repointed,
            'backup'      => basename($backupPath),
            'message'     => "Merged into Id={$survivorId}: repointed {$repointed} properties row(s), deleted {$duplicates->count()} dictionary row(s).",
        ];
    }

    /**
     * KEEP SEPARATE: edit the name fields (nameEn, namePt, nameEnSc, namePtSc) on one
     * or more rows so they are no longer duplicates. Only the fields actually supplied
     * per row are written. nameEn (the grouping key) must stay non-empty, unique in the
     * table, and unique amongst the submitted renames.
     *
     * decision.renames shape:
     *   { "<dictId>": { nameEn?, namePt?, nameEnSc?, namePtSc? }, ... }
     * A bare string value is still accepted and treated as { nameEn: value } for
     * backward compatibility.
     */
    private function applyKeepSeparate(array $decision, array $group): array
    {
        $allowed = ['nameEn', 'namePt', 'nameEnSc', 'namePtSc'];

        $renames = [];
        foreach ((array) ($decision['renames'] ?? []) as $id => $fields) {
            $id = (int) $id;
            if (!is_array($fields)) {
                $fields = ['nameEn' => (string) $fields];   // legacy bare-string = nameEn
            }
            $clean = [];
            foreach ($allowed as $f) {
                if (array_key_exists($f, $fields)) {
                    $clean[$f] = trim((string) $fields[$f]);
                }
            }
            if (!empty($clean)) {
                $renames[$id] = $clean;
            }
        }

        if (empty($renames)) {
            return ['action' => 'keep_separate', 'name' => $group['name'], 'message' => 'Kept separate — no changes applied.'];
        }

        // Validate ownership + nameEn rules (nameEn is the grouping key).
        $newNameEns = [];
        foreach ($renames as $id => $fields) {
            if (!in_array($id, $group['actionableIds'], true)) {
                throw new \RuntimeException("Cannot edit Id {$id}: it is not part of this group.");
            }
            if (array_key_exists('nameEn', $fields)) {
                $newName = $fields['nameEn'];
                if ($newName === '') {
                    throw new \RuntimeException("nameEn cannot be empty (Id {$id}).");
                }
                if ($this->nameExists($newName, $id)) {
                    throw new \RuntimeException("The name '{$newName}' is already taken by another property.");
                }
                $newNameEns[] = $newName;
            }
        }
        if (collect($newNameEns)->duplicates()->isNotEmpty()) {
            throw new \RuntimeException('Two rows were given the same new nameEn.');
        }

        $before = DB::table(self::DICT_TABLE)
            ->whereIn(self::DICT_ID, array_keys($renames))
            ->get();

        $backupPath = $this->writeBackupFile([
            'generated_at' => now()->toIso8601String(),
            'action'       => 'keep_separate',
            'name'         => $group['name'],
            'renamed_from' => $before,
            'renamed_to'   => $renames,
        ]);

        DB::transaction(function () use ($renames) {
            foreach ($renames as $id => $fields) {
                DB::table(self::DICT_TABLE)
                    ->where(self::DICT_ID, $id)
                    ->update($fields);
            }
        });

        return [
            'action'   => 'keep_separate',
            'name'      => $group['name'],
            'renamed'   => count($renames),
            'backup'    => basename($backupPath),
            'message'   => 'Kept separate — updated ' . count($renames) . ' row(s).',
        ];
    }

    /**
     * Repoint duplicates' referencing properties to the survivor, then delete the
     * duplicate dictionary rows. Runs no transaction itself — the caller controls
     * the transaction boundary. Returns the number of properties rows repointed in
     * the identity pass (Pass A).
     *
     * SET-BASED and correctly ordered for the whole group at once. Two repoint
     * passes run BEFORE any delete:
     *
     *   Pass A — identity repoint. Which properties rows BELONG to a duplicate is
     *     decided by propertyId only (a duplicate is identified by its Id, never by
     *     the non-unique GUID). These rows get propertyId = survId AND GUID = survGuid.
     *
     *   Pass B — dangling-GUID cleanup. A propertyId/GUID-disagreement row can still
     *     reference a duplicate by GUID while its propertyId points elsewhere; Pass A
     *     leaves that stale GUID in place, which would block the FK on delete. Pass B
     *     rewrites any remaining properties.GUID that equals a duplicate's GUID to the
     *     survivor's GUID (propertyId left untouched). Scoped to the duplicates' GUIDs
     *     and excluding the survivor's GUID.
     *
     * Only AFTER both passes are the duplicate dict rows deleted.
     *
     * Shared by the Artisan command (one transaction over many groups) and the
     * admin UI (one transaction per group).
     */
    public function repointDuplicatesToSurvivor($survivor, $duplicates): int
    {
        $duplicates = collect($duplicates);
        if ($duplicates->isEmpty()) {
            return 0;
        }

        $survId   = $survivor->{self::DICT_ID};
        $survGuid = (string) $survivor->{self::DICT_GUID};
        $dupIds   = $duplicates->pluck(self::DICT_ID)->all();

        // Pass A — identity repoint (match by propertyId only).
        $repointed = DB::table(self::PROP_TABLE)
            ->whereIn(self::PROP_PROPID, $dupIds)
            ->update([
                self::PROP_PROPID => $survId,
                self::PROP_GUID   => $survGuid,
            ]);

        // Pass B — dangling-GUID cleanup. Clear any remaining references to the
        // duplicates' GUIDs (disagreement rows whose propertyId pointed elsewhere) so
        // the FK won't block the delete. Only for GUIDs that differ from the survivor's.
        $dupGuids = $duplicates
            ->pluck(self::DICT_GUID)
            ->map(fn($guid) => (string) $guid)
            ->reject(fn($guid) => $guid === $survGuid)
            ->unique()
            ->values()
            ->all();

        if (!empty($dupGuids)) {
            DB::table(self::PROP_TABLE)
                ->whereIn(self::PROP_GUID, $dupGuids)
                ->update([self::PROP_GUID => $survGuid]);
        }

        // Delete the duplicate dict rows.
        //
        //   - Different-GUID duplicates: after Pass B no child still references their
        //     GUID, so they delete cleanly under full FK enforcement.
        //   - Same-GUID duplicates (survivor shares the GUID): the survivor still
        //     supplies that GUID so deletion is orphan-free, but properties.GUID ->
        //     dict.GUID references a NON-UNIQUE column and InnoDB blocks the delete
        //     anyway (it checks the deleted value, not whether another parent still
        //     provides it). For ONLY this case we disable FK checks for the delete and
        //     restore them immediately.
        $sameGuidIds = $duplicates
            ->filter(fn($d) => (string) $d->{self::DICT_GUID} === $survGuid)
            ->pluck(self::DICT_ID)->all();
        $otherGuidIds = $duplicates
            ->filter(fn($d) => (string) $d->{self::DICT_GUID} !== $survGuid)
            ->pluck(self::DICT_ID)->all();

        if (!empty($otherGuidIds)) {
            DB::table(self::DICT_TABLE)->whereIn(self::DICT_ID, $otherGuidIds)->delete();
        }

        if (!empty($sameGuidIds)) {
            $conn = DB::connection();
            $conn->statement('SET FOREIGN_KEY_CHECKS=0');
            try {
                DB::table(self::DICT_TABLE)->whereIn(self::DICT_ID, $sameGuidIds)->delete();
            } finally {
                $conn->statement('SET FOREIGN_KEY_CHECKS=1');
            }
        }

        return $repointed;
    }

    /**
     * Write a merge backup in the same format the command has always used and
     * return the path.
     *
     * @param array $groups each: ['name'=>string, 'survivor'=>object, 'duplicates'=>iterable]
     */
    public function writeMergeBackup(array $groups): string
    {
        $payload = ['generated_at' => now()->toIso8601String(), 'groups' => []];

        foreach ($groups as $g) {
            $duplicates = collect($g['duplicates']);
            $dupIds   = $duplicates->pluck(self::DICT_ID)->all();

            $payload['groups'][] = [
                'name'              => $g['name'],
                'survivor'          => $g['survivor'],
                'deleted_dict_rows' => $duplicates->values(),
                // Match by propertyId only — GUID is not unique across dict rows.
                'affected_props'    => DB::table(self::PROP_TABLE)
                    ->whereIn(self::PROP_PROPID, $dupIds)
                    ->get(),
            ];
        }

        return $this->writeBackupFile($payload);
    }

    public function nameExists(string $nameEn, ?int $excludeId = null): bool
    {
        $q = DB::table(self::DICT_TABLE)->where(self::NAME_COL, $nameEn);
        if ($excludeId !== null) {
            $q->where(self::DICT_ID, '!=', $excludeId);
        }
        return $q->exists();
    }

    /**
     * True if $row is a version variant of $survivor: same GUID (same lineage) but a
     * different versionNumber/revisionNumber. These are legitimate versions and are
     * never merged or deleted. Different-GUID rows are true duplicates, not variants.
     */
    public function isVersionVariant($survivor, $row): bool
    {
        if ((string) $row->{self::DICT_GUID} !== (string) $survivor->{self::DICT_GUID}) {
            return false;
        }
        return (string) ($row->versionNumber ?? '') !== (string) ($survivor->versionNumber ?? '')
            || (string) ($row->revisionNumber ?? '') !== (string) ($survivor->revisionNumber ?? '');
    }

    /**
     * True if any compared definition column on the duplicate is non-empty and
     * differs from the survivor's value.
     */
    public function descriptionsConflict($survivor, $dup): bool
    {
        foreach (self::DESC_COLS as $col) {
            $survVal = trim((string) ($survivor->{$col} ?? ''));
            $dupVal  = trim((string) ($dup->{$col} ?? ''));
            if ($dupVal !== '' && $dupVal !== $survVal) {
                return true;
            }
        }
        return false;
    }

    /**
     * Compact label for reports/UI, e.g. "Id=99 GUID=… v1.1".
     */
    public function rowLabel($row): string
    {
        $get = fn($k) => is_array($row) ? ($row[$k] ?? null) : ($row->{$k} ?? null);
        return sprintf(
            'Id=%s GUID=%s v%s.%s',
            $get(self::DICT_ID) ?? $get('id'),
            $get(self::DICT_GUID) ?? $get('guid'),
            $get('versionNumber') ?? '?',
            $get('revisionNumber') ?? '?'
        );
    }

    // ----------------------------------------------------------------------------

    /**
     * Enrich a dictionary row with the properties that reference it and any
     * propertyId/GUID disagreements.
     */
    private function enrichRow($row): array
    {
        $refs = $this->referencingProperties($row);
        $mismatches = $refs->filter(fn($p) => $this->isMismatch($p))->values();

        return [
            'id'                    => (int) $row->{self::DICT_ID},
            'guid'                  => $row->{self::DICT_GUID},
            'nameEn'                => $row->{self::NAME_COL},
            'namePt'                => $row->namePt ?? null,
            'nameEnSc'              => $row->nameEnSc ?? null,
            'namePtSc'              => $row->namePtSc ?? null,
            'versionNumber'         => $row->versionNumber ?? null,
            'revisionNumber'        => $row->revisionNumber ?? null,
            'definitionEn'          => $row->definitionEn ?? null,
            'definitionPt'          => $row->definitionPt ?? null,
            'status'                => $row->status ?? null,
            'referenceCount'        => $refs->count(),
            'referencingProperties' => $refs->all(),
            'mismatchProperties'    => $mismatches->all(),
        ];
    }

    /**
     * Properties rows that reference $row, matched by propertyId only (a dict row is
     * identified by its Id; GUID is not unique so it must not be used to select rows).
     * Each row is annotated with the GUID/name of the dict row its propertyId points
     * at, so a disagreement with the property's own GUID can still be surfaced.
     */
    private function referencingProperties($row): Collection
    {
        return DB::table(self::PROP_TABLE . ' as p')
            ->leftJoin(self::DICT_TABLE . ' as d', 'd.' . self::DICT_ID, '=', 'p.' . self::PROP_PROPID)
            ->leftJoin('productdatatemplates as pdt', 'pdt.Id', '=', 'p.pdtID')
            ->leftJoin('groupofproperties as gop', 'gop.Id', '=', 'p.gopID')
            ->where('p.' . self::PROP_PROPID, $row->{self::DICT_ID})
            ->orderBy('p.pdtID')
            ->select(
                'p.*',
                'd.' . self::DICT_GUID . ' as _dictGuidByPropertyId',
                'd.' . self::NAME_COL . ' as _dictNameByPropertyId',
                'pdt.pdtNameEn as _pdtNameEn',
                'pdt.pdtNamePt as _pdtNamePt',
                'pdt.versionNumber as _pdtVersion',
                'pdt.revisionNumber as _pdtRevision',
                'gop.gopNameEn as _gopNameEn',
                'gop.gopNamePt as _gopNamePt',
                'd.namePt as _dictNamePtByPropertyId'
            )
            ->get();
    }

    /**
     * Update a single properties row's per-PDT descriptions. This is a deliberate,
     * standalone edit (not part of a merge) so it intentionally writes descriptionPt
     * as well as descriptionEn. Returns the new values.
     */
    public function updatePropertyDescription(int $propertyId, ?string $descriptionEn, ?string $descriptionPt): array
    {
        $row = DB::table(self::PROP_TABLE)->where(self::DICT_ID, $propertyId)->first();
        if (!$row) {
            throw new \RuntimeException("Properties row Id {$propertyId} no longer exists — please reload.");
        }

        DB::table(self::PROP_TABLE)
            ->where(self::DICT_ID, $propertyId)
            ->update([
                'descriptionEn' => $descriptionEn,
                'descriptionPt' => $descriptionPt,
            ]);

        return [
            'id'            => $propertyId,
            'descriptionEn' => $descriptionEn,
            'descriptionPt' => $descriptionPt,
        ];
    }

    /**
     * Update the dictionary-level general definition (definitionEn/definitionPt) of one
     * `propertiesdatadictionaries` row. Used by the "deduped properties" review page.
     */
    public function updateDictDefinition(int $dictId, ?string $definitionEn, ?string $definitionPt): array
    {
        $row = DB::table(self::DICT_TABLE)->where(self::DICT_ID, $dictId)->first();
        if (!$row) {
            throw new \RuntimeException("Dictionary row Id {$dictId} no longer exists — please reload.");
        }

        DB::table(self::DICT_TABLE)
            ->where(self::DICT_ID, $dictId)
            ->update([
                'definitionEn' => $definitionEn,
                'definitionPt' => $definitionPt,
            ]);

        return [
            'id'           => $dictId,
            'definitionEn' => $definitionEn,
            'definitionPt' => $definitionPt,
        ];
    }

    /**
     * Every dictionary row that is referenced by at least $minRefs properties rows
     * (i.e. shared across multiple PDT/group contexts — the result of dedup merges,
     * plus any naturally reused property). Returns the dictionary row (with its general
     * definition) together with all of its in-context instances and their per-PDT
     * descriptions, ready for the manual-review page.
     *
     * Resolved in three queries (no N+1): the shared Ids, the dictionary rows, and all
     * referencing properties joined to their PDT/group in one pass.
     *
     * Each returned row carries a `needsAttention` flag: true when any instance is
     * missing a description, or the instances disagree with each other.
     *
     * @return array<int,array>
     */
    public function sharedProperties(int $minRefs = 2): array
    {
        $ids = DB::table(self::PROP_TABLE)
            ->select(self::PROP_PROPID)
            ->whereNotNull(self::PROP_PROPID)
            ->groupBy(self::PROP_PROPID)
            ->havingRaw('COUNT(*) >= ?', [$minRefs])
            ->pluck(self::PROP_PROPID)
            ->map(fn($i) => (int) $i)
            ->all();

        if (empty($ids)) {
            return [];
        }

        $dictRows = DB::table(self::DICT_TABLE)
            ->whereIn(self::DICT_ID, $ids)
            ->orderBy(self::NAME_COL)
            ->orderBy(self::DICT_ID)
            ->get();

        $propsByDict = DB::table(self::PROP_TABLE . ' as p')
            ->leftJoin('productdatatemplates as pdt', 'pdt.Id', '=', 'p.pdtID')
            ->leftJoin('groupofproperties as gop', 'gop.Id', '=', 'p.gopID')
            ->whereIn('p.' . self::PROP_PROPID, $ids)
            ->orderBy('pdt.pdtNameEn')
            ->orderBy('p.pdtID')
            ->select(
                'p.Id',
                'p.' . self::PROP_PROPID . ' as propertyId',
                'p.pdtID',
                'p.gopID',
                'p.descriptionEn',
                'p.descriptionPt',
                'pdt.pdtNameEn as _pdtNameEn',
                'pdt.pdtNamePt as _pdtNamePt',
                'pdt.versionNumber as _pdtVersion',
                'pdt.revisionNumber as _pdtRevision',
                'gop.gopNameEn as _gopNameEn',
                'gop.gopNamePt as _gopNamePt'
            )
            ->get()
            ->groupBy('propertyId');

        $out = [];
        foreach ($dictRows as $row) {
            $refs = collect($propsByDict->get($row->{self::DICT_ID}, collect()))->values();

            $en = $refs->map(fn($r) => trim((string) $r->descriptionEn));
            $pt = $refs->map(fn($r) => trim((string) $r->descriptionPt));
            $needsAttention = $en->contains('') || $pt->contains('')
                || $en->unique()->count() > 1 || $pt->unique()->count() > 1;

            $out[] = [
                'id'             => (int) $row->{self::DICT_ID},
                'guid'           => $row->{self::DICT_GUID},
                'nameEn'         => $row->nameEn,
                'namePt'         => $row->namePt,
                'nameEnSc'       => $row->nameEnSc,
                'namePtSc'       => $row->namePtSc,
                'definitionEn'   => $row->definitionEn,
                'definitionPt'   => $row->definitionPt,
                'status'         => $row->status,
                'refCount'       => $refs->count(),
                'needsAttention' => $needsAttention,
                'refs'           => $refs->all(),
            ];
        }

        return $out;
    }

    /**
     * A referencing property disagrees with itself when the dictionary row its
     * propertyId points to is missing, or has a different GUID than the property's
     * own GUID.
     */
    private function isMismatch($prop): bool
    {
        return $prop->_dictGuidByPropertyId === null
            || (string) $prop->_dictGuidByPropertyId !== (string) $prop->{self::PROP_GUID};
    }

    /**
     * Count of properties rows referencing the given duplicate rows, matched by
     * propertyId only (GUID is not unique so it must not be used to select rows).
     */
    private function countReferencing($duplicates): int
    {
        $duplicates = collect($duplicates);
        if ($duplicates->isEmpty()) {
            return 0;
        }
        $dupIds = $duplicates->pluck(self::DICT_ID)->all();

        return DB::table(self::PROP_TABLE)
            ->whereIn(self::PROP_PROPID, $dupIds)
            ->count();
    }

    private function resolveDefinition($spec, $survivorValue): ?string
    {
        if (is_array($spec) && ($spec['mode'] ?? 'survivor') === 'custom') {
            return (string) ($spec['value'] ?? '');
        }
        return $survivorValue;
    }

    /**
     * Path to the JSON file holding non-DB review state (which shared properties the
     * reviewer has marked "dealt with"). Deliberately outside the database.
     */
    public function reviewStatePath(): string
    {
        return storage_path('app/dedupe_review_state.json');
    }

    /**
     * Load the review state.
     *
     * @return array{dealtWith: array<int,bool>}
     */
    public function reviewState(): array
    {
        $path = $this->reviewStatePath();
        $dealt = [];
        if (is_file($path)) {
            $data = json_decode((string) file_get_contents($path), true);
            foreach ((array) ($data['dealtWith'] ?? []) as $id => $v) {
                if ($v) {
                    $dealt[(int) $id] = true;
                }
            }
        }
        return ['dealtWith' => $dealt];
    }

    /**
     * Mark / unmark one shared property (dictionary Id) as dealt-with, persisting to the
     * review-state file. Returns the updated state.
     *
     * @return array{dealtWith: array<int,bool>}
     */
    public function setDealtWith(int $dictId, bool $dealt): array
    {
        $state = $this->reviewState();
        if ($dealt) {
            $state['dealtWith'][$dictId] = true;
        } else {
            unset($state['dealtWith'][$dictId]);
        }

        // JSON object keys must be strings; ksort for a stable, human-diffable file.
        $out = [];
        foreach (array_keys($state['dealtWith']) as $id) {
            $out[(string) $id] = true;
        }
        ksort($out, SORT_NUMERIC);

        file_put_contents(
            $this->reviewStatePath(),
            json_encode(['dealtWith' => (object) $out], JSON_PRETTY_PRINT)
        );

        return $state;
    }

    private function writeBackupFile(array $payload): string
    {
        $stamp = now()->format('Ymd_His');
        $path  = storage_path("app/dedupe_backup_{$stamp}.json");
        $i = 1;
        while (file_exists($path)) {
            $path = storage_path("app/dedupe_backup_{$stamp}_{$i}.json");
            $i++;
        }
        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $path;
    }
}
