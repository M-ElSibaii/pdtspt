<?php

namespace App\Services;

use App\Support\LegacyDates;
use App\Support\Lineage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * New versions of a published PDT are made through an offline DRAFT.
 *
 * "Create new version" copies the current version into a Preview draft of the SAME lineage
 * (same GUID; its GOPs keep their GUIDs too; context rows point at the same dictionary
 * rows). The draft is edited over as many sessions as needed in the Preview editor — every
 * edit writes in place on draft-owned rows, and editing a shared dictionary definition
 * forks a Preview copy ({@see PreviewService::editPreviewProperty}) — so the live version
 * is never touched. {@see diff()} compares the draft with the version it was copied from
 * and works out the version/revision bump; publishing ({@see PreviewService::applyPublish})
 * promotes the draft to Active with the next numbers and retires the old version.
 *
 * While editing, the draft carries the numbers of the version it was copied from; the
 * real ones are assigned at publish. Public pages rank lineage rows by status
 * ({@see Lineage}), so the draft never surfaces there.
 *
 * Bump rules are the ones {@see VersioningService} applies:
 *   PDT  : version if its name changed or a group was added/removed; else revision.
 *   GOP  : version if its name changed or a property was added/removed; else revision
 *          (attribute change, or a contained property / usage changed).
 */
class DraftVersionService
{
    private const PDT  = 'productdatatemplates';
    private const GOP  = 'groupofproperties';
    private const PROP = 'properties';
    private const DICT = 'propertiesdatadictionaries';

    private const PDT_NAME = ['pdtNameEn', 'pdtNamePt'];
    private const GOP_NAME = ['gopNameEn', 'gopNamePt'];

    public function __construct(private SchemaAttributeService $schema)
    {
    }

    // ============================================================ lineage lookups

    /** The Preview draft of a published lineage, or null. */
    public function draftFor(string $guid): ?object
    {
        return Lineage::versionDraft(DB::table(self::PDT)->where('GUID', $guid)->get());
    }

    /**
     * The published version a draft will replace: the lineage's Active head (or its newest
     * InActive version when nothing is Active). Null for a brand-new PDT that was never
     * published.
     */
    public function baseOf(object $draft): ?object
    {
        return DB::table(self::PDT)->where('GUID', $draft->GUID)
            ->where('Id', '<>', $draft->Id)
            ->where('status', '<>', VersioningService::ST_PREVIEW)
            ->orderByRaw(Lineage::PREFER_PUBLISHED)
            ->first();
    }

    /** Next [version, revision] for a lineage, counted from its newest published row. */
    public function nextNumbers(string $table, string $guid, string $bump): array
    {
        $head = DB::table($table)->where('GUID', $guid)->where('status', '<>', VersioningService::ST_PREVIEW)
            ->orderByDesc('versionNumber')->orderByDesc('revisionNumber')->first();
        $v = (int) ($head->versionNumber ?? 0);
        $r = (int) ($head->revisionNumber ?? 0);

        return $bump === 'version' ? [$v + 1, 0] : [$v, $r + 1];
    }

    // ============================================================ start a draft

    /**
     * Open the new-version draft for a published PDT, creating it on first use. A lineage
     * has at most one draft, so asking again returns the existing one (no duplicate work).
     *
     * @return array{draftId:int, created:bool}
     */
    public function startDraft(int $pdtId): array
    {
        $pdt = DB::table(self::PDT)->where('Id', $pdtId)->first();
        if (!$pdt) {
            throw new \RuntimeException("PDT Id {$pdtId} not found.");
        }
        if ($pdt->status === VersioningService::ST_PREVIEW) {
            throw new \RuntimeException("PDT Id {$pdtId} is already a draft — open it in the draft editor.");
        }

        $existing = $this->draftFor($pdt->GUID);
        if ($existing) {
            return ['draftId' => (int) $existing->Id, 'created' => false];
        }

        $head = DB::table(self::PDT)->where('GUID', $pdt->GUID)->orderByRaw(Lineage::PREFER_PUBLISHED)->first();
        if ((int) $head->Id !== (int) $pdt->Id) {
            throw new \RuntimeException("v{$pdt->versionNumber}.{$pdt->revisionNumber} is not the current version of "
                . "'{$pdt->pdtNameEn}' (v{$head->versionNumber}.{$head->revisionNumber} is) — start the new version from that one.");
        }

        $today = Carbon::today()->toDateString();

        $draftId = DB::transaction(function () use ($pdt, $today) {
            $row = (array) $pdt;
            unset($row['Id']);
            $row['status'] = VersioningService::ST_PREVIEW;
            $row['depreciationDate'] = null;
            $row['depreciationExplanation'] = null;
            $row['updated_at'] = $today;
            $draftId = DB::table(self::PDT)->insertGetId(LegacyDates::clean($row), 'Id');

            $gopMap = [];
            foreach (DB::table(self::GOP)->where('pdtId', $pdt->Id)->orderBy('Id')->get() as $g) {
                $gRow = (array) $g;
                unset($gRow['Id']);
                $gRow['pdtId'] = $draftId;
                $gRow['status'] = VersioningService::ST_PREVIEW;
                $gRow['depreciationDate'] = null;
                $gRow['depreciationExplanation'] = null;
                $gopMap[$g->Id] = DB::table(self::GOP)->insertGetId(LegacyDates::clean($gRow), 'Id');
            }

            foreach (DB::table(self::PROP)->where('pdtID', $pdt->Id)->orderBy('Id')->get() as $c) {
                if (!isset($gopMap[$c->gopID])) {
                    continue; // context row of a group that no longer exists — nothing to carry
                }
                $cRow = (array) $c;
                unset($cRow['Id']);
                $cRow['pdtID'] = $draftId;
                $cRow['gopID'] = $gopMap[$c->gopID];
                DB::table(self::PROP)->insert(LegacyDates::clean($cRow));
            }

            return $draftId;
        });

        return ['draftId' => (int) $draftId, 'created' => true];
    }

    // ============================================================ diff

    /**
     * Everything that differs between a draft and the version it will replace, with the
     * bump each element needs. Writes nothing.
     *
     * For a brand-new PDT (no published version) returns isVersionDraft=false and a
     * content summary instead of a diff.
     */
    public function diff(int $draftId): array
    {
        $draft = DB::table(self::PDT)->where('Id', $draftId)->first();
        if (!$draft || $draft->status !== VersioningService::ST_PREVIEW) {
            throw new \RuntimeException("PDT Id {$draftId} is not a draft.");
        }

        $base = $this->baseOf($draft);
        $draftGops = DB::table(self::GOP)->where('pdtId', $draft->Id)->orderBy('Id')->get();
        $draftCtx = $this->contexts($draft->Id);

        if (!$base) {
            return [
                'isVersionDraft' => false,
                'draft' => $draft,
                'base' => null,
                'hasChanges' => true,
                'bump' => null,
                'allowedBumps' => [],
                'next' => ['version' => [1, 0], 'revision' => [1, 0]],
                'publishAs' => [(int) $draft->versionNumber, (int) $draft->revisionNumber],
                'pdtChanges' => [],
                'gops' => $draftGops->map(fn($g) => [
                    'guid' => $g->GUID, 'draftGopId' => (int) $g->Id, 'baseGopId' => null,
                    'nameEn' => $g->gopNameEn, 'namePt' => $g->gopNamePt, 'state' => 'added',
                    'changes' => [], 'propsAdded' => $this->names($draftCtx->where('gopID', $g->Id)),
                    'propsRemoved' => [], 'propsChanged' => [], 'bump' => null, 'reasons' => [],
                    'from' => null, 'to' => [(int) $g->versionNumber, (int) $g->revisionNumber],
                ])->values()->all(),
                'reasons' => ['First publication of a new template.'],
                'counts' => $this->counts($draftGops, $draftCtx),
            ];
        }

        $baseGops = DB::table(self::GOP)->where('pdtId', $base->Id)->orderBy('Id')->get();
        $baseCtx = $this->contexts($base->Id);
        $dictRows = DB::table(self::DICT)
            ->whereIn('Id', $draftCtx->pluck('propertyId')->merge($baseCtx->pluck('propertyId'))->filter()->unique()->values())
            ->get()->keyBy('Id');

        $pdtFields = $this->schema->editable(self::PDT);
        $gopFields = $this->schema->editable(self::GOP);
        $ctxFields = $this->schema->editable(self::PROP);
        $dictFields = $this->schema->editable(self::DICT);

        $pdtChanges = $this->fieldChanges($base, $draft, $pdtFields);
        $reasons = [];
        $gops = [];
        $baseByGuid = $baseGops->keyBy('GUID');
        $draftGuids = $draftGops->pluck('GUID')->all();

        foreach ($draftGops as $g) {
            $bg = $baseByGuid->get($g->GUID);
            $dCtx = $draftCtx->where('gopID', $g->Id);

            if (!$bg) {
                $gops[] = [
                    'guid' => $g->GUID, 'draftGopId' => (int) $g->Id, 'baseGopId' => null,
                    'nameEn' => $g->gopNameEn, 'namePt' => $g->gopNamePt, 'state' => 'added',
                    'changes' => [], 'propsAdded' => $this->names($dCtx), 'propsRemoved' => [], 'propsChanged' => [],
                    'bump' => null, 'reasons' => [], 'from' => null, 'to' => [1, 0],
                ];
                $reasons[] = "Group '{$g->gopNameEn}' added";
                continue;
            }

            $bCtx = $baseCtx->where('gopID', $bg->Id);
            $changes = $this->fieldChanges($bg, $g, $gopFields);
            [$added, $removed, $changed] = $this->compareContexts($bCtx, $dCtx, $dictRows, $ctxFields, $dictFields);

            $gopReasons = [];
            if (array_intersect(array_keys($changes), self::GOP_NAME)) {
                $gopReasons[] = 'name changed';
            }
            if ($added) {
                $gopReasons[] = count($added) . ' propert' . (count($added) === 1 ? 'y' : 'ies') . ' added';
            }
            if ($removed) {
                $gopReasons[] = count($removed) . ' propert' . (count($removed) === 1 ? 'y' : 'ies') . ' removed';
            }
            $bump = $gopReasons ? 'version' : (($changes || $changed) ? 'revision' : null);
            if ($bump === 'revision') {
                if ($changes) {
                    $gopReasons[] = 'attributes changed';
                }
                if ($changed) {
                    $gopReasons[] = count($changed) . ' propert' . (count($changed) === 1 ? 'y' : 'ies') . ' changed';
                }
            }

            $gops[] = [
                'guid' => $g->GUID, 'draftGopId' => (int) $g->Id, 'baseGopId' => (int) $bg->Id,
                'nameEn' => $g->gopNameEn, 'namePt' => $g->gopNamePt,
                'state' => $bump ? 'modified' : 'unchanged',
                'changes' => $changes, 'propsAdded' => $added, 'propsRemoved' => $removed, 'propsChanged' => $changed,
                'bump' => $bump, 'reasons' => $gopReasons,
                'from' => [(int) $bg->versionNumber, (int) $bg->revisionNumber],
                'to' => $bump ? $this->nextNumbers(self::GOP, $g->GUID, $bump) : [(int) $bg->versionNumber, (int) $bg->revisionNumber],
            ];
            if ($bump) {
                $reasons[] = "Group '{$g->gopNameEn}': " . implode(', ', $gopReasons);
            }
        }

        foreach ($baseGops as $bg) {
            if (in_array($bg->GUID, $draftGuids, true)) {
                continue;
            }
            $gops[] = [
                'guid' => $bg->GUID, 'draftGopId' => null, 'baseGopId' => (int) $bg->Id,
                'nameEn' => $bg->gopNameEn, 'namePt' => $bg->gopNamePt, 'state' => 'removed',
                'changes' => [], 'propsAdded' => [], 'propsRemoved' => $this->names($baseCtx->where('gopID', $bg->Id)),
                'propsChanged' => [], 'bump' => null, 'reasons' => [],
                'from' => [(int) $bg->versionNumber, (int) $bg->revisionNumber], 'to' => null,
            ];
            $reasons[] = "Group '{$bg->gopNameEn}' removed";
        }

        $nameChanged = (bool) array_intersect(array_keys($pdtChanges), self::PDT_NAME);
        $gopSetChanged = collect($gops)->contains(fn($g) => in_array($g['state'], ['added', 'removed'], true));
        $anyGopChange = collect($gops)->contains(fn($g) => $g['state'] !== 'unchanged');

        if ($nameChanged) {
            array_unshift($reasons, 'Template name changed');
        } elseif ($pdtChanges) {
            array_unshift($reasons, 'Template attributes changed (' . implode(', ', array_keys($pdtChanges)) . ')');
        }

        $bump = ($nameChanged || $gopSetChanged) ? 'version' : (($pdtChanges || $anyGopChange) ? 'revision' : null);

        return [
            'isVersionDraft' => true,
            'draft' => $draft,
            'base' => $base,
            'hasChanges' => $bump !== null,
            'bump' => $bump,
            // A revision may always be promoted to a version; a version cannot be downgraded.
            'allowedBumps' => $bump === 'version' ? ['version'] : ($bump === 'revision' ? ['revision', 'version'] : []),
            'next' => [
                'version' => $this->nextNumbers(self::PDT, $draft->GUID, 'version'),
                'revision' => $this->nextNumbers(self::PDT, $draft->GUID, 'revision'),
            ],
            'pdtChanges' => $pdtChanges,
            'gops' => $gops,
            'reasons' => $reasons,
            'counts' => $this->counts($draftGops, $draftCtx),
        ];
    }

    /**
     * Per-group change state of a draft for the editor's badges:
     * [draftGopId => 'added'|'modified'|'unchanged'] and [contextId => 'added'|'changed'].
     */
    public function markers(int $draftId): array
    {
        $d = $this->diff($draftId);
        $gops = [];
        $ctx = [];
        foreach ($d['gops'] as $g) {
            if ($g['draftGopId'] === null) {
                continue;
            }
            $gops[$g['draftGopId']] = $d['isVersionDraft'] ? $g['state'] : 'added';
            if ($d['isVersionDraft']) {
                foreach ($g['propsAdded'] as $p) {
                    $ctx[$p['contextId']] = 'added';
                }
                foreach ($g['propsChanged'] as $p) {
                    $ctx[$p['contextId']] = 'changed';
                }
            }
        }

        return ['gops' => $gops, 'contexts' => $ctx, 'diff' => $d];
    }

    // ============================================================ helpers

    private function contexts(int $pdtId)
    {
        return DB::table(self::PROP . ' as p')
            ->leftJoin(self::DICT . ' as d', 'd.Id', '=', 'p.propertyId')
            ->where('p.pdtID', $pdtId)
            ->orderBy('p.Id')
            ->get(['p.*', 'd.nameEn as dictNameEn', 'd.namePt as dictNamePt', 'd.nameEnSc as dictNameEnSc',
                'd.versionNumber as dictVersion', 'd.revisionNumber as dictRevision', 'd.status as dictStatus']);
    }

    /**
     * Pair a group's context rows by property lineage (dictionary GUID) and classify them.
     *
     * @return array{0:array,1:array,2:array} [added, removed, changed]
     */
    private function compareContexts($baseCtx, $draftCtx, $dictRows, array $ctxFields, array $dictFields): array
    {
        $baseBy = $baseCtx->groupBy('GUID')->map(fn($rows) => $rows->values());
        $draftBy = $draftCtx->groupBy('GUID')->map(fn($rows) => $rows->values());
        $added = [];
        $removed = [];
        $changed = [];

        foreach ($draftBy as $guid => $rows) {
            $baseRows = $baseBy->get($guid, collect());
            foreach ($rows as $i => $c) {
                $b = $baseRows->get($i);
                if (!$b) {
                    $added[] = $this->label($c);
                    continue;
                }
                $usage = $this->fieldChanges($b, $c, $ctxFields);
                $definition = [];
                $versionSwitch = null;
                if ((int) $b->propertyId !== (int) $c->propertyId) {
                    $bd = $dictRows->get($b->propertyId);
                    $dd = $dictRows->get($c->propertyId);
                    if ($bd && $dd) {
                        $definition = $this->fieldChanges($bd, $dd, $dictFields);
                    }
                    $versionSwitch = ['from' => $b->dictVersion . '.' . $b->dictRevision, 'to' => $c->dictVersion . '.' . $c->dictRevision,
                        'draftStatus' => $c->dictStatus];
                }
                if ($usage || $definition || $versionSwitch) {
                    $changed[] = $this->label($c) + ['usage' => $usage, 'definition' => $definition, 'versionSwitch' => $versionSwitch];
                }
            }
        }
        foreach ($baseBy as $guid => $rows) {
            $draftRows = $draftBy->get($guid, collect());
            foreach ($rows as $i => $b) {
                if (!$draftRows->get($i)) {
                    $removed[] = $this->label($b);
                }
            }
        }

        return [$added, $removed, $changed];
    }

    /** [field => ['old'=>..., 'new'=>...]] for the fields whose value differs (null ≡ ''). */
    private function fieldChanges($old, $new, array $fields): array
    {
        $out = [];
        foreach ($fields as $f) {
            $o = (string) ($old->{$f} ?? '');
            $n = (string) ($new->{$f} ?? '');
            if (trim($o) !== trim($n)) {
                $out[$f] = ['old' => $old->{$f} ?? null, 'new' => $new->{$f} ?? null];
            }
        }

        return $out;
    }

    private function label($c): array
    {
        return [
            'contextId' => (int) $c->Id,
            'guid' => $c->GUID,
            'nameEn' => $c->dictNameEnSc ?: $c->dictNameEn,
            'code' => $c->dictNameEn,
        ];
    }

    private function names($ctx): array
    {
        return $ctx->map(fn($c) => $this->label($c))->values()->all();
    }

    private function counts($gops, $ctx): array
    {
        return [
            'gops' => $gops->count(),
            'properties' => $ctx->count(),
            'emptyGops' => $gops->filter(fn($g) => $ctx->where('gopID', $g->Id)->isEmpty())->pluck('gopNameEn')->values()->all(),
        ];
    }
}
