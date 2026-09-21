<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Deduplication for `referencedocuments`, the sibling of {@see DictionaryDedupeService}.
 *
 * Reference documents carry no versionNumber, so there is no "version variant" case here.
 * What there is instead is a trap: several rows legitimately SHARE A TITLE while being
 * genuinely different documents, because a standard's amendments and corrigenda keep the
 * title of the base standard —
 *
 *     EN 474-1:2006+A6:2019          "Earth-moving machinery - Safety - Part 1: ..."
 *     EN 474-1:2006+A4:2013/AC:2014  "Earth-moving machinery - Safety - Part 1: ..."
 *
 * Merging those would destroy real information. So the two signals are kept apart:
 *
 *   - SAME NAME  (rdName, trimmed and case-folded) — a true duplicate. These are the rows
 *     that hand out one identifier to two records, and they are directly actionable.
 *   - SAME TITLE, different name — a POSSIBLE duplicate, surfaced for a human to judge
 *     and never merged without an explicit acknowledgement.
 *
 * A merge repoints every referencing row in all four tables that cite a reference
 * document, then deletes the now-unused rows. A JSON backup is written first and the
 * whole thing runs in one transaction.
 */
class ReferenceDocumentDedupeService
{
    public const TABLE = 'referencedocuments';
    public const KEY   = 'GUID';
    public const NAME  = 'rdName';
    public const TITLE = 'title';

    /** Every table that cites a reference document, and the column that does it. */
    public const REFERENCING = [
        'properties'            => 'referenceDocumentGUID',
        'groupofproperties'     => 'referenceDocumentGUID',
        'productdatatemplates'  => 'referenceDocumentGUID',
        'constructionobjects'   => 'referenceDocumentGUID',
    ];

    /** Groups whose members share a name: true duplicates, safe to act on. */
    public const KIND_NAME = 'name';

    /** Groups whose members only share a title: amendments look like this, so review first. */
    public const KIND_TITLE = 'title';

    /**
     * Verify the schema this tool depends on. Returns an error message, or null if OK.
     */
    public function schemaError(): ?string
    {
        foreach ([self::NAME, self::TITLE, self::KEY] as $col) {
            if (!Schema::hasColumn(self::TABLE, $col)) {
                return "Column '{$col}' not found on " . self::TABLE . '.';
            }
        }
        foreach (self::REFERENCING as $table => $column) {
            if (!Schema::hasTable($table)) {
                return "Table '{$table}' not found.";
            }
            if (!Schema::hasColumn($table, $column)) {
                return "Column '{$column}' not found on {$table}.";
            }
        }

        return null;
    }

    /** Compare names and titles trimmed, whitespace-collapsed and case-folded. */
    public static function normalise(?string $value): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', (string) $value)));
    }

    private static function key(string $kind, string $normalised): string
    {
        return $kind . ':' . $normalised;
    }

    // ------------------------------------------------------------------ analysis

    /**
     * Every duplicate group, name-matched first and then title-matched.
     *
     * @return array<int,array>
     */
    public function analyzeGroups(): array
    {
        $rows = DB::table(self::TABLE)->orderBy(self::NAME)->get();

        $usage = $this->usageCounts();

        $byName = [];
        foreach ($rows as $row) {
            $name = self::normalise($row->{self::NAME} ?? null);
            if ($name === '' || $name === 'n/a') continue;
            $byName[$name][] = $row;
        }

        $groups = [];
        $claimed = [];   // GUIDs already shown in a name group

        foreach ($byName as $name => $members) {
            if (count($members) < 2) continue;
            foreach ($members as $m) $claimed[$m->{self::KEY}] = true;
            $groups[] = $this->buildGroup(self::KIND_NAME, $name, $members, $usage);
        }

        // Same title, different names: amendments and corrigenda land here, so these are
        // shown for judgement rather than treated as duplicates.
        $byTitle = [];
        foreach ($rows as $row) {
            if (isset($claimed[$row->{self::KEY}])) continue;
            $title = self::normalise($row->{self::TITLE} ?? null);
            if ($title === '' || $title === 'n/a') continue;
            $byTitle[$title][] = $row;
        }

        foreach ($byTitle as $title => $members) {
            if (count($members) < 2) continue;
            $groups[] = $this->buildGroup(self::KIND_TITLE, $title, $members, $usage);
        }

        return $groups;
    }

    /**
     * One group, addressed by its key ("name:<normalised>" / "title:<normalised>").
     * Returns null once it is no longer a duplicate group.
     */
    public function analyzeGroup(string $key): ?array
    {
        foreach ($this->analyzeGroups() as $group) {
            if ($group['key'] === $key) return $group;
        }

        return null;
    }

    /**
     * @param array<int,object> $members
     * @param array<string,array<string,int>> $usage
     */
    private function buildGroup(string $kind, string $normalised, array $members, array $usage): array
    {
        usort($members, fn($a, $b) => ($usage[$b->{self::KEY}]['total'] ?? 0) <=> ($usage[$a->{self::KEY}]['total'] ?? 0));

        $documents = array_map(fn($row) => $this->enrich($row, $usage), $members);
        $guids = array_map(fn($d) => $d['guid'], $documents);
        sort($guids);

        return [
            'key'            => self::key($kind, $normalised),
            'kind'           => $kind,
            // The label shows the value as stored, not the folded form.
            'label'          => $kind === self::KIND_NAME
                ? trim((string) $members[0]->{self::NAME})
                : trim((string) $members[0]->{self::TITLE}),
            'documents'      => $documents,
            'guids'          => $guids,
            // A title group's members are differently NAMED, which is exactly what an
            // amendment looks like; merging one needs an explicit acknowledgement.
            'needsAcknowledgement' => $kind === self::KIND_TITLE,
            'namesDiffer'    => count(array_unique(array_map(fn($d) => self::normalise($d['rdName']), $documents))) > 1,
            'titlesDiffer'   => count(array_unique(array_map(fn($d) => self::normalise($d['title']), $documents))) > 1,
            'affectedCount'  => array_sum(array_map(fn($d) => $d['usage']['total'], $documents)),
        ];
    }

    private function enrich($row, array $usage): array
    {
        $guid = (string) $row->{self::KEY};

        return [
            'guid'        => $guid,
            'rdName'      => (string) ($row->{self::NAME} ?? ''),
            'title'       => (string) ($row->{self::TITLE} ?? ''),
            'description' => (string) ($row->description ?? ''),
            'status'      => $row->status ?? null,
            'uri'         => $this->uriFor($row),
            'usage'       => $usage[$guid] ?? array_fill_keys(array_merge(array_keys(self::REFERENCING), ['total']), 0),
            'usedBy'      => $this->usedBy($guid),
        ];
    }

    private function uriFor($row): ?string
    {
        try {
            return UriService::build(UriService::DOCUMENT, $row);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * How many rows cite each reference document, per table plus a total.
     * One grouped query per referencing table, rather than one per document.
     *
     * @return array<string,array<string,int>>
     */
    public function usageCounts(): array
    {
        $out = [];

        foreach (self::REFERENCING as $table => $column) {
            $counts = DB::table($table)
                ->select($column . ' as guid', DB::raw('COUNT(*) as cnt'))
                ->whereNotNull($column)
                ->groupBy($column)
                ->pluck('cnt', 'guid');

            foreach ($counts as $guid => $count) {
                $out[$guid][$table] = (int) $count;
                $out[$guid]['total'] = ($out[$guid]['total'] ?? 0) + (int) $count;
            }
        }

        foreach ($out as $guid => $row) {
            foreach (self::REFERENCING as $table => $column) {
                $out[$guid][$table] = $row[$table] ?? 0;
            }
        }

        return $out;
    }

    /**
     * A few named examples of what cites this document, so a reviewer can see what a
     * merge would touch without reading the database.
     */
    private function usedBy(string $guid, int $limit = 6): array
    {
        $out = [];

        $properties = DB::table('properties as p')
            ->leftJoin('propertiesdatadictionaries as d', 'd.Id', '=', 'p.propertyId')
            ->leftJoin('productdatatemplates as pdt', 'pdt.Id', '=', 'p.pdtID')
            ->where('p.referenceDocumentGUID', $guid)
            ->select('p.Id', 'd.namePt', 'd.nameEn', 'pdt.pdtNamePt')
            ->limit($limit)->get();

        foreach ($properties as $row) {
            $out[] = [
                'kind'  => 'property',
                'label' => trim(($row->namePt ?: $row->nameEn ?: ('#' . $row->Id))
                    . ($row->pdtNamePt ? ' — ' . $row->pdtNamePt : '')),
            ];
        }

        foreach (['groupofproperties' => ['gopNamePt', 'group'],
                  'productdatatemplates' => ['pdtNamePt', 'data template'],
                  'constructionobjects' => ['constructionObjectNamePt', 'construction object']] as $table => [$nameCol, $kind]) {
            $rows = DB::table($table)->where('referenceDocumentGUID', $guid)
                ->select($nameCol . ' as name')->limit($limit)->get();
            foreach ($rows as $row) {
                $out[] = ['kind' => $kind, 'label' => (string) $row->name];
            }
        }

        return $out;
    }

    // ------------------------------------------------------------------- mutation

    /**
     * Apply one group's decision.
     *
     * The group is re-analysed server-side and the decision is rejected if its member set
     * changed since the page was rendered, so two reviewers cannot act on stale data.
     *
     *   action: 'merge' | 'keep_separate' | 'skip'
     *   merge:         survivorGuid, expectedGuids[], acknowledgeDifferentNames(bool)
     *   keep_separate: edits{ guid: { rdName?, title?, description? } }
     */
    public function applyDecision(array $decision): array
    {
        $key = (string) ($decision['key'] ?? '');
        if ($key === '') {
            throw new \InvalidArgumentException('Missing group key.');
        }

        $group = $this->analyzeGroup($key);
        if (!$group) {
            throw new \RuntimeException("Group '{$key}' is no longer a duplicate group. The data changed — please reload.");
        }

        $expected = collect($decision['expectedGuids'] ?? [])->map(fn($g) => (string) $g)->sort()->values()->all();
        if ($expected !== $group['guids']) {
            throw new \RuntimeException('This group changed since you loaded the page. Reload and try again.');
        }

        switch ($decision['action'] ?? null) {
            case 'skip':
                return ['action' => 'skip', 'key' => $key, 'message' => 'Skipped — no changes made.'];
            case 'keep_separate':
                return $this->applyKeepSeparate($decision, $group);
            case 'merge':
                return $this->applyMerge($decision, $group);
            default:
                throw new \InvalidArgumentException("Unknown action '" . (string) ($decision['action'] ?? '') . "'.");
        }
    }

    /**
     * MERGE: repoint every citing row onto the survivor, then delete the other documents.
     * The survivor's own fields are left exactly as they are — this tool removes
     * duplicates, it does not rewrite a document's content.
     */
    private function applyMerge(array $decision, array $group): array
    {
        $survivorGuid = (string) ($decision['survivorGuid'] ?? '');
        if (!in_array($survivorGuid, $group['guids'], true)) {
            throw new \RuntimeException('The chosen survivor is not part of this group.');
        }

        // Differently-named documents are usually different editions of a standard, not
        // duplicates. Merging them has to be a deliberate act.
        if (($group['needsAcknowledgement'] || $group['namesDiffer']) && empty($decision['acknowledgeDifferentNames'])) {
            throw new \RuntimeException(
                'These documents do not share a name — amendments and corrigenda of a standard keep the '
                . 'title of the base standard, so they look like this. Confirm they really are the same '
                . 'document before merging.'
            );
        }

        $duplicates = array_values(array_diff($group['guids'], [$survivorGuid]));
        if (!$duplicates) {
            throw new \RuntimeException('Nothing to merge — the group has a single document.');
        }

        $backupPath = $this->writeBackup([
            'action'     => 'merge',
            'key'        => $group['key'],
            'survivor'   => DB::table(self::TABLE)->where(self::KEY, $survivorGuid)->first(),
            'deleted'    => DB::table(self::TABLE)->whereIn(self::KEY, $duplicates)->get(),
            'references' => $this->collectReferences($duplicates),
        ]);

        $repointed = [];
        DB::transaction(function () use ($survivorGuid, $duplicates, &$repointed) {
            foreach (self::REFERENCING as $table => $column) {
                $n = DB::table($table)
                    ->whereIn($column, $duplicates)
                    ->update([$column => $survivorGuid]);
                if ($n > 0) $repointed[$table] = $n;
            }

            // Safe now: nothing points at them any more.
            DB::table(self::TABLE)->whereIn(self::KEY, $duplicates)->delete();
        });

        RefDocs::flush();

        $total = array_sum($repointed);

        return [
            'action'       => 'merge',
            'key'          => $group['key'],
            'survivorGuid' => $survivorGuid,
            'deleted'      => count($duplicates),
            'repointed'    => $repointed,
            'backup'       => basename($backupPath),
            'message'      => 'Merged into ' . $survivorGuid . ': repointed ' . $total
                . ' citation(s), deleted ' . count($duplicates) . ' document(s).',
        ];
    }

    /**
     * KEEP SEPARATE: edit the name/title/description so the documents are distinguishable.
     * Validated against the state the edit would produce: the rows must end up with
     * different names, otherwise nothing has actually been separated.
     */
    private function applyKeepSeparate(array $decision, array $group): array
    {
        $allowed = ['rdName', 'title', 'description'];

        $edits = [];
        foreach ((array) ($decision['edits'] ?? []) as $guid => $fields) {
            $guid = (string) $guid;
            if (!in_array($guid, $group['guids'], true)) {
                throw new \RuntimeException("Cannot edit {$guid}: it is not part of this group.");
            }
            $clean = [];
            foreach ($allowed as $field) {
                if (array_key_exists($field, (array) $fields)) {
                    $clean[$field] = trim((string) $fields[$field]);
                }
            }
            if ($clean) $edits[$guid] = $clean;
        }

        if (!$edits) {
            return ['action' => 'keep_separate', 'key' => $group['key'], 'message' => 'Kept separate — no changes applied.'];
        }

        $names = [];
        foreach ($group['documents'] as $doc) {
            $names[$doc['guid']] = $edits[$doc['guid']]['rdName'] ?? $doc['rdName'];
        }
        foreach ($names as $guid => $name) {
            if (trim((string) $name) === '') {
                throw new \RuntimeException("The name cannot be empty ({$guid}).");
            }
        }

        $seen = [];
        foreach ($names as $guid => $name) {
            $normalised = self::normalise($name);
            if (isset($seen[$normalised])) {
                throw new \RuntimeException(
                    "{$seen[$normalised]} and {$guid} would still share the name '{$name}', so they would "
                    . 'remain one duplicate group. Give them different names, or merge them.'
                );
            }
            $seen[$normalised] = $guid;
        }

        foreach ($edits as $guid => $fields) {
            if (!isset($fields['rdName']) || $fields['rdName'] === '') continue;
            $clash = DB::table(self::TABLE)
                ->whereRaw('LOWER(TRIM(' . self::NAME . ')) = ?', [self::normalise($fields['rdName'])])
                ->whereNotIn(self::KEY, $group['guids'])
                ->value(self::KEY);
            if ($clash) {
                throw new \RuntimeException("The name '{$fields['rdName']}' is already used by document {$clash}.");
            }
        }

        $backupPath = $this->writeBackup([
            'action' => 'keep_separate',
            'key'    => $group['key'],
            'before' => DB::table(self::TABLE)->whereIn(self::KEY, array_keys($edits))->get(),
            'after'  => $edits,
        ]);

        DB::transaction(function () use ($edits) {
            foreach ($edits as $guid => $fields) {
                DB::table(self::TABLE)->where(self::KEY, $guid)->update($fields);
            }
        });

        RefDocs::flush();

        return [
            'action'  => 'keep_separate',
            'key'     => $group['key'],
            'edited'  => count($edits),
            'backup'  => basename($backupPath),
            'message' => 'Kept separate — updated ' . count($edits) . ' document(s).',
        ];
    }

    /** Every row that cites one of these documents, captured for the backup. */
    private function collectReferences(array $guids): array
    {
        $out = [];
        foreach (self::REFERENCING as $table => $column) {
            $rows = DB::table($table)->whereIn($column, $guids)->get();
            if ($rows->isNotEmpty()) $out[$table] = $rows;
        }

        return $out;
    }

    /**
     * Same location and shape as the dictionary tool's backups (storage/app), so both
     * tools' undo material sits in one place.
     */
    private function writeBackup(array $payload): string
    {
        $payload = ['generated_at' => now()->toIso8601String()] + $payload;

        $stamp = now()->format('Ymd_His');
        $path  = storage_path("app/dedupe_refdocs_backup_{$stamp}.json");
        $i = 1;
        while (file_exists($path)) {
            $path = storage_path("app/dedupe_refdocs_backup_{$stamp}_{$i}.json");
            $i++;
        }
        file_put_contents($path, json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        return $path;
    }
}
