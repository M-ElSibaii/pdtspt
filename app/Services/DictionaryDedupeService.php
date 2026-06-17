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
     * KEEP SEPARATE: optionally rename one or more nameEn values so the rows are no
     * longer duplicates. New names are validated to be non-empty, unique in the
     * table, and unique amongst the submitted renames.
     */
    private function applyKeepSeparate(array $decision, array $group): array
    {
        $renames = collect($decision['renames'] ?? [])
            ->mapWithKeys(fn($v, $id) => [(int) $id => trim((string) $v)])
            ->filter(fn($v) => $v !== '');

        if ($renames->isEmpty()) {
            return ['action' => 'keep_separate', 'name' => $group['name'], 'message' => 'Kept separate — no renames applied.'];
        }

        // No collisions amongst the submitted new names.
        if ($renames->values()->duplicates()->isNotEmpty()) {
            throw new \RuntimeException('Two rows were given the same new name.');
        }

        foreach ($renames as $id => $newName) {
            if (!in_array($id, $group['actionableIds'], true)) {
                throw new \RuntimeException("Cannot rename Id {$id}: it is not part of this group.");
            }
            if ($this->nameExists($newName, $id)) {
                throw new \RuntimeException("The name '{$newName}' is already taken by another property.");
            }
        }

        $before = DB::table(self::DICT_TABLE)
            ->whereIn(self::DICT_ID, $renames->keys()->all())
            ->get();

        $backupPath = $this->writeBackupFile([
            'generated_at' => now()->toIso8601String(),
            'action'       => 'keep_separate',
            'name'         => $group['name'],
            'renamed_from' => $before,
            'renamed_to'   => $renames->all(),
        ]);

        DB::transaction(function () use ($renames) {
            foreach ($renames as $id => $newName) {
                DB::table(self::DICT_TABLE)
                    ->where(self::DICT_ID, $id)
                    ->update([self::NAME_COL => $newName]);
            }
        });

        return [
            'action'   => 'keep_separate',
            'name'      => $group['name'],
            'renamed'   => $renames->count(),
            'backup'    => basename($backupPath),
            'message'   => 'Kept separate — renamed ' . $renames->count() . ' row(s).',
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
                'gop.gopNamePt as _gopNamePt'
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
