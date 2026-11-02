<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * The admin "status health check": what {@see VersioningService::recomputeStatuses()}
 * would change, explained row by row, plus structural problems that no status change can
 * fix and need a person to look at. Applying is opt-in and can be limited to the rows the
 * admin ticks; a JSON backup of the affected rows is written first.
 */
class StatusHealthService
{
    private const PDT  = 'productdatatemplates';
    private const GOP  = 'groupofproperties';
    private const PROP = 'properties';
    private const DICT = 'propertiesdatadictionaries';
    private const CO   = 'constructionobjects';

    /** Human labels per table, in hierarchy order (also the display order). */
    public const TABLES = [
        self::PDT  => 'Product data templates',
        self::GOP  => 'Groups of properties',
        self::DICT => 'Dictionary properties',
        self::CO   => 'Construction objects',
    ];

    public function __construct(private VersioningService $versioning)
    {
    }

    /**
     * @return array{changes: array, byTable: array, issues: array, total: int}
     */
    public function report(): array
    {
        $changes = $this->describe($this->versioning->recomputeStatuses(false));

        $byTable = [];
        foreach (array_keys(self::TABLES) as $table) {
            $rows = array_values(array_filter($changes, fn($c) => $c['table'] === $table));
            if ($rows) {
                $byTable[$table] = $rows;
            }
        }

        return [
            'changes' => $changes,
            'byTable' => $byTable,
            'issues'  => $this->issues(),
            'total'   => count($changes),
        ];
    }

    /** Number of pending status fixes plus structural issues (for the admin hub badge). */
    public function count(): int
    {
        return count($this->versioning->recomputeStatuses(false)) + count($this->issues());
    }

    /**
     * Apply the selected fixes. Keys are "{table}:{id}" as returned by {@see report()};
     * changes are recomputed fresh, so a stale page can only apply what is still needed.
     *
     * @return array{applied:int, skipped:int, backup:?string}
     */
    public function apply(array $keys): array
    {
        $keys = array_flip(array_map('strval', $keys));
        $all = $this->versioning->recomputeStatuses(false);
        $chosen = array_values(array_filter($all, fn($c) => isset($keys[$this->key($c)])));

        if (!$chosen) {
            return ['applied' => 0, 'skipped' => count($keys), 'backup' => null];
        }

        $backup = ['generated_at' => now()->toIso8601String(), 'changes' => $chosen];
        $path = storage_path('app/status_check_backup_' . now()->format('Ymd_His') . '.json');
        file_put_contents($path, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->versioning->applyStatusChanges($chosen);

        return ['applied' => count($chosen), 'skipped' => count($keys) - count($chosen), 'backup' => basename($path)];
    }

    // ============================================================ describe

    private function key(array $c): string
    {
        return $c['table'] . ':' . $c['id'];
    }

    private function describe(array $changes): array
    {
        $ids = collect($changes)->groupBy('table')->map(fn($rows) => $rows->pluck('id')->all());

        $pdts = DB::table(self::PDT)->get()->keyBy('Id');
        $gops = DB::table(self::GOP)->whereIn('Id', $ids->get(self::GOP, []))->get()->keyBy('Id');
        $dicts = DB::table(self::DICT)->whereIn('Id', $ids->get(self::DICT, []))->get()->keyBy('Id');
        $cos = DB::table(self::CO)->whereIn('GUID', $ids->get(self::CO, []))->get()->keyBy('GUID');
        $usedBy = DB::table(self::PROP)->whereIn('propertyId', $ids->get(self::DICT, []))->get(['propertyId', 'pdtID'])
            ->groupBy('propertyId');

        return array_map(function ($c) use ($pdts, $gops, $dicts, $cos, $usedBy) {
            $c['key'] = $this->key($c);
            $c['name'] = '?';
            $c['version'] = null;
            $c['pdtId'] = null;

            switch ($c['table']) {
                case self::PDT:
                    $r = $pdts->get($c['id']);
                    $c['name'] = $r->pdtNameEn ?? '?';
                    $c['version'] = $r ? "v{$r->versionNumber}.{$r->revisionNumber}" : null;
                    $c['pdtId'] = $c['id'];
                    $c['reason'] = $c['to'] === VersioningService::ST_ACTIVE
                        ? 'Newest published version of its lineage'
                        : 'A newer published version exists';
                    break;

                case self::GOP:
                    $r = $gops->get($c['id']);
                    $p = $r ? $pdts->get($r->pdtId) : null;
                    $c['name'] = $r->gopNameEn ?? '?';
                    $c['version'] = $r ? "v{$r->versionNumber}.{$r->revisionNumber}" : null;
                    $c['pdtId'] = $r->pdtId ?? null;
                    $c['reason'] = 'Follows its template' . ($p ? " '{$p->pdtNameEn}' v{$p->versionNumber}.{$p->revisionNumber}" : '');
                    break;

                case self::DICT:
                    $r = $dicts->get($c['id']);
                    $c['name'] = $r ? ($r->nameEnSc ?: $r->nameEn) : '?';
                    $c['version'] = $r ? "v{$r->versionNumber}.{$r->revisionNumber}" : null;
                    $users = ($usedBy->get($c['id']) ?? collect())->pluck('pdtID')->unique()
                        ->map(fn($id) => $pdts->get($id))->filter();
                    // Name only the templates that justify the target status.
                    $names = function (string $status) use ($users) {
                        $hits = $users->where('status', $status);
                        return $hits->map(fn($p) => "{$p->pdtNameEn} v{$p->versionNumber}.{$p->revisionNumber}")->take(3)->implode(', ')
                            . ($hits->count() > 3 ? ' and ' . ($hits->count() - 3) . ' more' : '');
                    };
                    $c['reason'] = match ($c['to']) {
                        VersioningService::ST_ACTIVE => 'Used by Active template ' . $names(VersioningService::ST_ACTIVE),
                        VersioningService::ST_PREVIEW => 'Only used by drafts: ' . $names(VersioningService::ST_PREVIEW),
                        default => $users->isEmpty() ? 'Not used by any template' : 'Only used by retired versions: ' . $names(VersioningService::ST_INACTIVE),
                    };
                    break;

                case self::CO:
                    $r = $cos->get($c['id']);
                    $c['name'] = $r->constructionObjectNameEn ?? '?';
                    $c['version'] = $r ? "v{$r->versionNumber}.{$r->revisionNumber}" : null;
                    $c['reason'] = match ($c['to']) {
                        VersioningService::ST_ACTIVE => 'Typed by an Active template',
                        VersioningService::ST_PREVIEW => 'Only typed by drafts',
                        default => 'Not typed by any Active template',
                    };
                    break;
            }

            return $c;
        }, $changes);
    }

    // ============================================================ structural issues

    /**
     * Problems a status change cannot fix. Each: severity (error|warning), title, detail,
     * and the rows involved (with a PDT id to link to where there is one).
     */
    public function issues(): array
    {
        $issues = [];
        $pdts = DB::table(self::PDT)->get();

        foreach ($pdts->groupBy('GUID') as $rows) {
            $active = $rows->where('status', VersioningService::ST_ACTIVE);
            $drafts = $rows->where('status', VersioningService::ST_PREVIEW);
            $name = $rows->first()->pdtNameEn;

            if ($active->count() > 1) {
                $issues[] = $this->issue('error', "'{$name}' has {$active->count()} Active versions",
                    'Only the newest version should be Active. The status fixes above retire the older ones.',
                    $active->map(fn($r) => ['label' => "v{$r->versionNumber}.{$r->revisionNumber}", 'pdtId' => $r->Id])->values()->all());
            }
            if ($drafts->count() > 1 && $rows->count() > $drafts->count()) {
                $issues[] = $this->issue('warning', "'{$name}' has {$drafts->count()} new-version drafts",
                    'A template should have at most one draft in progress. Publish or discard the extra ones from the Drafts page.',
                    $drafts->map(fn($r) => ['label' => "Draft Id {$r->Id}", 'pdtId' => $r->Id, 'draft' => true])->values()->all());
            }
        }

        $empty = DB::table(self::PDT . ' as p')->where('p.status', VersioningService::ST_ACTIVE)
            ->whereNotExists(fn($q) => $q->from(self::GOP . ' as g')->whereColumn('g.pdtId', 'p.Id'))
            ->get(['p.Id', 'p.pdtNameEn', 'p.versionNumber', 'p.revisionNumber']);
        if ($empty->isNotEmpty()) {
            $issues[] = $this->issue('warning', $empty->count() . ' Active template(s) without any group of properties',
                'Published templates with no content. Create a new version to add groups, or check whether they should exist.',
                $empty->map(fn($r) => ['label' => "{$r->pdtNameEn} v{$r->versionNumber}.{$r->revisionNumber}", 'pdtId' => $r->Id])->all());
        }

        $broken = DB::table(self::PROP . ' as c')->leftJoin(self::DICT . ' as d', 'd.Id', '=', 'c.propertyId')
            ->whereNull('d.Id')->get(['c.Id', 'c.pdtID', 'c.propertyId']);
        if ($broken->isNotEmpty()) {
            $issues[] = $this->issue('error', $broken->count() . ' property usage(s) point at a missing dictionary property',
                'These rows of the properties table have no dictionary definition, so they cannot be shown or exported.',
                $broken->take(50)->map(fn($r) => ['label' => "properties.Id {$r->Id} (dict Id " . ($r->propertyId ?? 'NULL') . ')', 'pdtId' => $r->pdtID])->all());
        }

        $misfiled = DB::table(self::PROP . ' as c')->join(self::GOP . ' as g', 'g.Id', '=', 'c.gopID')
            ->whereColumn('g.pdtId', '<>', 'c.pdtID')->get(['c.Id', 'c.pdtID', 'g.pdtId as gopPdtId']);
        if ($misfiled->isNotEmpty()) {
            $issues[] = $this->issue('error', $misfiled->count() . ' property usage(s) sit in a group of another template',
                'The row says it belongs to one template while its group belongs to another.',
                $misfiled->take(50)->map(fn($r) => ['label' => "properties.Id {$r->Id} (template {$r->pdtID}, group's template {$r->gopPdtId})", 'pdtId' => $r->pdtID])->all());
        }

        $orphans = DB::table(self::DICT . ' as d')->where('d.status', VersioningService::ST_PREVIEW)
            ->whereNotExists(fn($q) => $q->from(self::PROP . ' as c')->whereColumn('c.propertyId', 'd.Id'))
            ->get(['d.Id', 'd.nameEn', 'd.versionNumber', 'd.revisionNumber']);
        if ($orphans->isNotEmpty()) {
            $issues[] = $this->issue('warning', $orphans->count() . ' draft dictionary propert' . ($orphans->count() === 1 ? 'y is' : 'ies are') . ' not used anywhere',
                'Preview definitions left behind (e.g. created in a draft and then removed). Harmless, but they clutter the dictionary.',
                $orphans->take(50)->map(fn($r) => ['label' => "{$r->nameEn} v{$r->versionNumber}.{$r->revisionNumber} (Id {$r->Id})"])->all());
        }

        return $issues;
    }

    private function issue(string $severity, string $title, string $detail, array $rows): array
    {
        return compact('severity', 'title', 'detail', 'rows');
    }
}
