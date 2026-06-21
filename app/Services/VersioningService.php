<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Versioning engine for the PDT data model (PhD "Table 2", ISO 23386:2020 §5.2).
 *
 * MODEL (verified in Phase 0 + data probe):
 *   - Lineage key is GUID. All rows sharing a GUID are versions of one element,
 *     ordered by (versionNumber, revisionNumber). A new version/revision ALWAYS
 *     creates a NEW ROW with the SAME GUID and a NEW Id.
 *   - SNAPSHOT model: each PDT version owns its OWN groupofproperties + properties
 *     rows. Versioning clones the whole subtree into new rows (same GUIDs, new Ids);
 *     the prior version is left fully intact except status -> InActive (+ deprecation).
 *     Children are NEVER reparented — old snapshots are the ISO audit trail.
 *   - "Property" = a propertiesdatadictionaries row (the SHARED element where
 *     name/definition/attributes live). A `properties` row is a per-(PDT,GOP) CONTEXT
 *     link; it is cloned per snapshot, never shared across PDT versions.
 *   - A groupofproperties row belongs to exactly one PDT (pdtId).
 *
 * CASCADE BREADTH (narrowed, confirmed): a property edit re-snapshots ONLY the PDT(s)
 * in the editing session (opts.scopePdtIds). Other Active PDTs keep referencing the
 * OLD property version and stay Active (selective adoption). The use-gate deprecates
 * the old dict row only when no OTHER active PDT still references it.
 *
 * STATUS: 'Active' | 'InActive' | 'Preview'. Preview is excluded from this engine.
 *
 * STAGED CONTRACT: planX() computes a Plan from a net diff and writes NOTHING.
 * applyPlan() commits the whole Plan in ONE transaction (JSON backup first).
 */
class VersioningService
{
    public const ST_ACTIVE   = 'Active';
    public const ST_INACTIVE = 'InActive';
    public const ST_PREVIEW  = 'Preview';

    private const DICT = 'propertiesdatadictionaries';
    private const GOP  = 'groupofproperties';
    private const PROP = 'properties';
    private const PDT  = 'productdatatemplates';
    private const CO   = 'constructionobjects';

    /** Dict fields whose change triggers a VERSION bump (name/definition). */
    private const DICT_NAME_DEF = ['nameEn', 'namePt', 'nameEnSc', 'namePtSc', 'definitionEn', 'definitionPt'];

    /** Dict fields whose change triggers a REVISION bump (attribute change). */
    private const DICT_ATTRS = [
        'dataType', 'units', 'dimension', 'physicalQuantity', 'dynamicProperty',
        'parametersOfTheDynamicProperty', 'namesOfDefiningValues', 'definingValues',
        'tolerance', 'digitalFormat', 'textFormat', 'listOfPossibleValuesInLanguageN',
        'boundaryValues', 'countryOfUse', 'countryOfOrigin', 'creatorsLanguage',
        'relationToOtherDataDictionaries',
    ];

    /** GOP name fields whose change triggers a VERSION bump. */
    private const GOP_NAME = ['gopNameEn', 'gopNamePt'];

    /** GOP attribute fields whose change triggers a REVISION bump. */
    private const GOP_ATTRS = [
        'definitionEn', 'definitionPt', 'countryOfUse', 'countryOfOrigin',
        'creatorsLanguage', 'categoryOfGroupOfProperties', 'parentGroupOfProperties',
        'relationToOtherDataDictionaries',
    ];

    /** PDT name fields whose change triggers a VERSION bump. */
    private const PDT_NAME = ['pdtNameEn', 'pdtNamePt'];

    // ============================================================ GUID + lineage

    public function newGuid(): string
    {
        return GuidGenerator::generateUnique();
    }

    private function head(string $table, string $guid)
    {
        return DB::table($table)->where('GUID', $guid)
            ->orderByDesc('versionNumber')->orderByDesc('revisionNumber')->first();
    }

    /** [version, revision] for the next row given a bump kind and the lineage head. */
    private function nextVR($head, string $bump): array
    {
        $v = (int) $head->versionNumber;
        $r = (int) $head->revisionNumber;
        return $bump === 'version' ? [$v + 1, 0] : [$v, $r + 1];
    }

    private function anyChanged($old, array $newValues, array $fields): bool
    {
        foreach ($fields as $f) {
            if (array_key_exists($f, $newValues) && (string) $newValues[$f] !== (string) ($old->{$f} ?? '')) {
                return true;
            }
        }
        return false;
    }

    // ============================================================ bump decisions

    public function decidePropertyBump($old, array $newValues, bool $isCorrection = false): string
    {
        $nd = $this->anyChanged($old, $newValues, self::DICT_NAME_DEF);
        $at = $this->anyChanged($old, $newValues, self::DICT_ATTRS);
        if (!$nd && !$at) {
            return 'none';
        }
        return ($nd && !$isCorrection) ? 'version' : 'revision';
    }

    // ============================================================ PLAN: property

    /**
     * New version/revision of a dictionary property, re-snapshotting only the PDTs in
     * $opts['scopePdtIds'] (selective adoption). Writes nothing.
     *
     * @param array $opts ['correction'=>bool, 'scopePdtIds'=>int[]]  scopePdtIds = the
     *              Active PDTs to re-snapshot adopting the new version; [] = create the
     *              new dict version only (adopted later), leaving the old one in place.
     */
    public function planPropertyVersion(int $dictId, array $newValues, array $opts = []): array
    {
        $old = DB::table(self::DICT)->where('Id', $dictId)->first();
        if (!$old) {
            throw new \RuntimeException("Dictionary property Id {$dictId} not found.");
        }

        $bump = $this->decidePropertyBump($old, $newValues, (bool) ($opts['correction'] ?? false));
        if ($bump === 'none') {
            return $this->emptyPlan('property', $dictId, 'No name/definition/attribute change — nothing to version.');
        }

        $guid = $old->GUID;
        [$nv, $nr] = $this->nextVR($this->head(self::DICT, $guid), $bump);
        $scope = array_values(array_unique(array_map('intval', $opts['scopePdtIds'] ?? [])));

        $ops = [];
        $summary = [];

        // New shared dict row (same GUID, new Id).
        $dVals = $this->cloneForNewVersion((array) $old, $newValues, array_merge(self::DICT_NAME_DEF, self::DICT_ATTRS), $nv, $nr);
        $dVals['listOfReplacedProperties'] = "v{$old->versionNumber}.{$old->revisionNumber}";
        $ops[] = ['type' => 'insert', 'ref' => 'D', 'table' => self::DICT, 'values' => $dVals];
        $summary[] = "CREATE dict property '{$old->nameEn}' v{$nv}.{$nr} (same GUID {$guid}) — status Active";

        // Re-snapshot each scoped Active PDT, adopting the new dict version.
        foreach ($scope as $pdtId) {
            $pdt = DB::table(self::PDT)->where('Id', $pdtId)->first();
            if (!$pdt || $pdt->status !== self::ST_ACTIVE) {
                $summary[] = "SKIP PDT Id={$pdtId} (not an Active PDT)";
                continue;
            }
            $ctx = DB::table(self::PROP)->where('pdtID', $pdtId)->where('propertyId', $dictId)->get();
            if ($ctx->isEmpty()) {
                $summary[] = "SKIP PDT Id={$pdtId} (does not reference this property)";
                continue;
            }
            $remap = [];
            $gopBumps = [];
            foreach ($ctx as $c) {
                $remap[$c->Id] = ['propertyId' => ['__ref' => 'D', 'col' => 'Id'], 'GUID' => ['__ref' => 'D', 'col' => 'GUID']];
                $gopBumps[$c->gopID] = 'revision'; // the containing GOP got a new property version
            }
            $this->cloneSubtree($pdt, [
                'pdtBump'              => 'revision',
                'gopBumps'             => $gopBumps,
                'contextPropertyRemap' => $remap,
            ], $ops, $summary);
        }

        // Use-gate: deprecate the old dict row only if no OTHER active PDT still uses it.
        $otherActive = DB::table(self::PROP . ' as p')
            ->join(self::PDT . ' as pdt', 'pdt.Id', '=', 'p.pdtID')
            ->where('p.propertyId', $dictId)
            ->where('pdt.status', self::ST_ACTIVE)
            ->whereNotIn('p.pdtID', $scope ?: [0])
            ->count();
        if ($otherActive === 0) {
            $ops[] = ['type' => 'deprecate', 'table' => self::DICT, 'id' => $dictId,
                'explanation' => "Superseded by version {$nv}.{$nr}", 'replacing' => "v{$nv}.{$nr}"];
            $summary[] = "DEPRECATE old dict property Id={$dictId} v{$old->versionNumber}.{$old->revisionNumber} (no other active use)";
        } else {
            $summary[] = "KEEP old dict property Id={$dictId} Active ({$otherActive} other active context(s) — selective adoption)";
        }

        return ['rootType' => 'property', 'rootId' => $dictId, 'guid' => $guid, 'bump' => $bump, 'ops' => $ops, 'summary' => $summary];
    }

    // ============================================================ PLAN: GOP

    /**
     * New version/revision of a GOP within its PDT.
     *   version  : GOP name change OR a property was added/removed
     *   revision : attribute change (or, via cascade, a contained property versioned)
     * Cascades a REVISION bump to the GOP's PDT (snapshot clone).
     *
     * @param array $changes ['addProperties'=>int[] dictIds, 'removeContextIds'=>int[]]
     */
    public function planGopVersion(int $gopId, array $newAttrs = [], array $changes = []): array
    {
        $gop = DB::table(self::GOP)->where('Id', $gopId)->first();
        if (!$gop) {
            throw new \RuntimeException("GOP Id {$gopId} not found.");
        }
        $pdt = DB::table(self::PDT)->where('Id', $gop->pdtId)->first();
        if (!$pdt || $pdt->status !== self::ST_ACTIVE) {
            throw new \RuntimeException("GOP Id {$gopId} is not in an Active PDT — version the PDT/Preview flow instead.");
        }

        $nameChanged = $this->anyChanged($gop, $newAttrs, self::GOP_NAME);
        $attrChanged = $this->anyChanged($gop, $newAttrs, self::GOP_ATTRS);
        $added   = !empty($changes['addProperties']);
        $removed = !empty($changes['removeContextIds']);

        if (!$nameChanged && !$attrChanged && !$added && !$removed) {
            return $this->emptyPlan('gop', $gopId, 'No GOP change — nothing to version.');
        }
        $gopBump = ($nameChanged || $added || $removed) ? 'version' : 'revision';

        $overrides = [];
        foreach (array_merge(self::GOP_NAME, self::GOP_ATTRS) as $f) {
            if (array_key_exists($f, $newAttrs)) {
                $overrides[$f] = $newAttrs[$f];
            }
        }

        $ops = [];
        $summary = ["GOP '{$gop->gopNameEn}' Id={$gopId} → {$gopBump} bump; cascade PDT revision"];
        $addProps = array_map(fn($d) => ['gopId' => $gopId, 'dictId' => (int) $d], $changes['addProperties'] ?? []);

        $this->cloneSubtree($pdt, [
            'pdtBump'          => 'revision',
            'gopBumps'         => [$gopId => $gopBump],
            'gopOverrides'     => [$gopId => $overrides],
            'addProperties'    => $addProps,
            'removeContextIds' => $changes['removeContextIds'] ?? [],
        ], $ops, $summary);

        return ['rootType' => 'gop', 'rootId' => $gopId, 'guid' => $gop->GUID, 'bump' => $gopBump, 'ops' => $ops, 'summary' => $summary];
    }

    // ============================================================ PLAN: PDT (staged)

    /**
     * Staged, batch-commit PDT versioning: takes the WHOLE session's net diff and
     * returns ONE Plan. Writes nothing.
     *
     *   version  : PDT name change OR a GOP added/removed
     *   revision : otherwise, when anything nested changed (GOP edited, property
     *              versioned, PDT attribute changed)
     *
     * @param array $staged [
     *   'pdt'        => ['attributes'=>[field=>val]],
     *   'removeGopIds'=> int[],
     *   'addGops'    => [ ['fromGopId'=>?int,'attributes'=>[...],'properties'=>int[] dictIds] ],
     *   'gopEdits'   => [ ['gopId'=>int,'attributes'=>[...],'addProperties'=>int[],'removeContextIds'=>int[]] ],
     *   'propertyEdits' => [ ['gopId'=>int,'dictId'=>int,'values'=>[...],'correction'=>bool] ],
     * ]
     */
    public function planPdtVersion(int $pdtId, array $staged): array
    {
        $pdt = DB::table(self::PDT)->where('Id', $pdtId)->first();
        if (!$pdt || $pdt->status !== self::ST_ACTIVE) {
            throw new \RuntimeException("PDT Id {$pdtId} is not Active.");
        }

        $pdtAttrs   = $staged['pdt']['attributes'] ?? [];
        $removeGops = array_map('intval', $staged['removeGopIds'] ?? []);
        $addGops    = $staged['addGops'] ?? [];
        $gopEdits   = $staged['gopEdits'] ?? [];
        $propEdits  = $staged['propertyEdits'] ?? [];

        $pdtNameChanged = $this->anyChanged($pdt, $pdtAttrs, self::PDT_NAME);
        $gopAddedRemoved = !empty($addGops) || !empty($removeGops);
        $anythingNested = $gopAddedRemoved || !empty($gopEdits) || !empty($propEdits)
            || $this->anyChanged($pdt, $pdtAttrs, ['descriptionEn', 'descriptionPt', 'category', 'referenceDocumentGUID', 'constructionObjectGUID']);

        if (!$pdtNameChanged && !$anythingNested) {
            return $this->emptyPlan('pdt', $pdtId, 'No change staged — nothing to version.');
        }
        $pdtBump = ($pdtNameChanged || $gopAddedRemoved) ? 'version' : 'revision';

        $ops = [];
        $summary = ["STAGED PDT '{$pdt->pdtNameEn}' Id={$pdtId} → {$pdtBump} bump (one commit)"];

        $gopBumps = [];
        $gopOverrides = [];
        $addProperties = [];
        $removeContextIds = [];
        $remap = [];

        // Property edits → new dict version(s) + remap the matching context row + bump its GOP.
        foreach ($propEdits as $pe) {
            $dictId = (int) $pe['dictId'];
            $gopIdOfEdit = (int) $pe['gopId'];
            $old = DB::table(self::DICT)->where('Id', $dictId)->first();
            if (!$old) {
                continue;
            }
            $b = $this->decidePropertyBump($old, $pe['values'] ?? [], (bool) ($pe['correction'] ?? false));
            if ($b === 'none') {
                continue;
            }
            [$nv, $nr] = $this->nextVR($this->head(self::DICT, $old->GUID), $b);
            $ref = 'D' . $dictId;
            $dVals = $this->cloneForNewVersion((array) $old, $pe['values'] ?? [], array_merge(self::DICT_NAME_DEF, self::DICT_ATTRS), $nv, $nr);
            $dVals['listOfReplacedProperties'] = "v{$old->versionNumber}.{$old->revisionNumber}";
            $ops[] = ['type' => 'insert', 'ref' => $ref, 'table' => self::DICT, 'values' => $dVals];
            $summary[] = "CREATE dict '{$old->nameEn}' v{$nv}.{$nr} ({$b})";

            $ctx = DB::table(self::PROP)->where('pdtID', $pdtId)->where('gopID', $gopIdOfEdit)->where('propertyId', $dictId)->get();
            foreach ($ctx as $c) {
                $remap[$c->Id] = ['propertyId' => ['__ref' => $ref, 'col' => 'Id'], 'GUID' => ['__ref' => $ref, 'col' => 'GUID']];
            }
            $gopBumps[$gopIdOfEdit] = 'revision';

            // Use-gate for this dict, scoped to this single PDT.
            $otherActive = DB::table(self::PROP . ' as p')->join(self::PDT . ' as pdt', 'pdt.Id', '=', 'p.pdtID')
                ->where('p.propertyId', $dictId)->where('pdt.status', self::ST_ACTIVE)->where('p.pdtID', '<>', $pdtId)->count();
            if ($otherActive === 0) {
                $ops[] = ['type' => 'deprecate', 'table' => self::DICT, 'id' => $dictId,
                    'explanation' => "Superseded by version {$nv}.{$nr}", 'replacing' => "v{$nv}.{$nr}"];
            }
        }

        // GOP edits → bump (name/add/remove = version; attr = revision) + overrides.
        foreach ($gopEdits as $ge) {
            $gid = (int) $ge['gopId'];
            $g = DB::table(self::GOP)->where('Id', $gid)->first();
            if (!$g) {
                continue;
            }
            $attrs = $ge['attributes'] ?? [];
            $nameChanged = $this->anyChanged($g, $attrs, self::GOP_NAME);
            $added = !empty($ge['addProperties']);
            $removed = !empty($ge['removeContextIds']);
            $gopBumps[$gid] = ($nameChanged || $added || $removed) ? 'version' : ($gopBumps[$gid] ?? 'revision');
            $ov = [];
            foreach (array_merge(self::GOP_NAME, self::GOP_ATTRS) as $f) {
                if (array_key_exists($f, $attrs)) {
                    $ov[$f] = $attrs[$f];
                }
            }
            $gopOverrides[$gid] = $ov;
            foreach ($ge['addProperties'] ?? [] as $d) {
                $addProperties[] = ['gopId' => $gid, 'dictId' => (int) $d];
            }
            $removeContextIds = array_merge($removeContextIds, array_map('intval', $ge['removeContextIds'] ?? []));
        }

        $this->cloneSubtree($pdt, [
            'pdtBump'              => $pdtBump,
            'pdtOverrides'         => $pdtAttrs,
            'gopBumps'             => $gopBumps,
            'gopOverrides'         => $gopOverrides,
            'contextPropertyRemap' => $remap,
            'addProperties'        => $addProperties,
            'removeContextIds'     => $removeContextIds,
            'removeGopIds'         => $removeGops,
            'addGops'              => $addGops,
        ], $ops, $summary);

        return ['rootType' => 'pdt', 'rootId' => $pdtId, 'guid' => $pdt->GUID, 'bump' => $pdtBump, 'ops' => $ops, 'summary' => $summary];
    }

    // ============================================================ clone-subtree core

    /**
     * Append ops that clone an entire PDT subtree into a new version, then retire the
     * old subtree (status only — the rows stay as the audit-trail snapshot).
     *
     * Unchanged GOPs are carried forward at the SAME version/revision (new Id); only
     * GOPs in $spec['gopBumps'] are version/revision-bumped. Context rows are cloned;
     * $spec['contextPropertyRemap'][oldCtxId] overrides their propertyId/GUID.
     */
    private function cloneSubtree($pdt, array $spec, array &$ops, array &$summary): void
    {
        $pdtBump = $spec['pdtBump'] ?? 'revision';
        [$pv, $pr] = $this->nextVR($this->head(self::PDT, $pdt->GUID), $pdtBump);
        $pRef = 'P' . $pdt->Id;
        $pVals = $this->cloneForNewVersion((array) $pdt, $spec['pdtOverrides'] ?? [], array_keys($spec['pdtOverrides'] ?? []), $pv, $pr);
        $ops[] = ['type' => 'insert', 'ref' => $pRef, 'table' => self::PDT, 'values' => $pVals];
        $summary[] = "CLONE PDT Id={$pdt->Id} → v{$pv}.{$pr} ({$pdtBump}); old kept as InActive snapshot";

        $gops = DB::table(self::GOP)->where('pdtId', $pdt->Id)->get();
        $ctxByGop = DB::table(self::PROP)->where('pdtID', $pdt->Id)->get()->groupBy('gopID');
        $removeGopIds = $spec['removeGopIds'] ?? [];
        $removeCtx = $spec['removeContextIds'] ?? [];

        foreach ($gops as $g) {
            if (in_array($g->Id, $removeGopIds)) {
                $summary[] = "  REMOVE GOP '{$g->gopNameEn}' Id={$g->Id} (dropped from new version)";
                continue;
            }
            $bump = $spec['gopBumps'][$g->Id] ?? null;
            $gOver = $spec['gopOverrides'][$g->Id] ?? [];
            $gRef = 'G' . $g->Id;

            if ($bump) {
                [$gv, $gr] = $this->nextVR($this->head(self::GOP, $g->GUID), $bump);
                $gVals = $this->cloneForNewVersion((array) $g, $gOver, array_keys($gOver), $gv, $gr);
                $gVals['listOfReplacedProperties'] = "v{$g->versionNumber}.{$g->revisionNumber}";
                $summary[] = "  GOP '{$g->gopNameEn}' Id={$g->Id} → v{$gv}.{$gr} ({$bump})";
            } else {
                $gVals = $this->cloneSnapshot((array) $g, $gOver); // SAME version/revision/dates
                $summary[] = "  GOP '{$g->gopNameEn}' Id={$g->Id} carried forward v{$g->versionNumber}.{$g->revisionNumber}";
            }
            $gVals['pdtId'] = ['__ref' => $pRef, 'col' => 'Id'];
            $ops[] = ['type' => 'insert', 'ref' => $gRef, 'table' => self::GOP, 'values' => $gVals];

            foreach (($ctxByGop[$g->Id] ?? collect()) as $c) {
                if (in_array($c->Id, $removeCtx)) {
                    $summary[] = "    remove property context Id={$c->Id}";
                    continue;
                }
                $cVals = $this->cloneSnapshot((array) $c, []);
                $cVals['pdtID'] = ['__ref' => $pRef, 'col' => 'Id'];
                $cVals['gopID'] = ['__ref' => $gRef, 'col' => 'Id'];
                if (isset($spec['contextPropertyRemap'][$c->Id])) {
                    $cVals['propertyId'] = $spec['contextPropertyRemap'][$c->Id]['propertyId'];
                    $cVals['GUID'] = $spec['contextPropertyRemap'][$c->Id]['GUID'];
                }
                $ops[] = ['type' => 'insert', 'ref' => null, 'table' => self::PROP, 'values' => $cVals];
            }

            foreach (($spec['addProperties'] ?? []) as $ai => $ap) {
                if ((int) $ap['gopId'] !== (int) $g->Id) {
                    continue;
                }
                if (!empty($ap['newProperty'])) {
                    // Brand-new dictionary property from scratch (fresh GUID, v1.0, Active).
                    $guid = GuidGenerator::normalize($ap['newProperty']['GUID'] ?? null) ?? GuidGenerator::generateUnique();
                    $ndRef = 'ND' . $ai;
                    $ops[] = ['type' => 'insert', 'ref' => $ndRef, 'table' => self::DICT,
                        'values' => $this->buildNewDictValues($ap['newProperty'], $guid)];
                    $propIdVal = ['__ref' => $ndRef, 'col' => 'Id'];
                    $guidVal = $guid;
                    $defEn = $ap['newProperty']['definitionEn'] ?? '';
                    $defPt = $ap['newProperty']['definitionPt'] ?? '';
                    $summary[] = "  ADD NEW property '" . ($ap['newProperty']['nameEn'] ?? 'new') . "' (new GUID {$guid}) to GOP '{$g->gopNameEn}'";
                } else {
                    $d = DB::table(self::DICT)->where('Id', $ap['dictId'])->first();
                    if (!$d) {
                        continue;
                    }
                    $propIdVal = $d->Id;
                    $guidVal = $d->GUID;
                    $defEn = $d->definitionEn ?? '';
                    $defPt = $d->definitionPt ?? '';
                    $summary[] = "  ADD property '{$d->nameEn}' to GOP '{$g->gopNameEn}'";
                }
                $ops[] = ['type' => 'insert', 'ref' => null, 'table' => self::PROP, 'values' => [
                    'GUID' => $guidVal, 'propertyId' => $propIdVal,
                    'gopID' => ['__ref' => $gRef, 'col' => 'Id'], 'pdtID' => ['__ref' => $pRef, 'col' => 'Id'],
                    'descriptionEn' => $defEn, 'descriptionPt' => $defPt,
                    'referenceDocumentGUID' => 'n/a',
                ]];
            }
        }

        // Added GOPs (from existing = fresh GUID clone, or from scratch).
        $i = 0;
        foreach (($spec['addGops'] ?? []) as $ag) {
            $base = !empty($ag['fromGopId']) ? (array) DB::table(self::GOP)->where('Id', $ag['fromGopId'])->first() : [];
            $newGuid = GuidGenerator::generateUnique();
            $gVals = $this->cloneSnapshotFresh($base, $ag['attributes'] ?? [], $newGuid);
            $gVals['pdtId'] = ['__ref' => $pRef, 'col' => 'Id'];
            $gRef = 'NG' . ($i++);
            $ops[] = ['type' => 'insert', 'ref' => $gRef, 'table' => self::GOP, 'values' => $gVals];
            $summary[] = "  ADD GOP '" . ($gVals['gopNameEn'] ?? '?') . "' (new GUID {$newGuid})";
            foreach (($ag['properties'] ?? []) as $dictId) {
                $d = DB::table(self::DICT)->where('Id', $dictId)->first();
                if (!$d) {
                    continue;
                }
                $ops[] = ['type' => 'insert', 'ref' => null, 'table' => self::PROP, 'values' => [
                    'GUID' => $d->GUID, 'propertyId' => $d->Id,
                    'gopID' => ['__ref' => $gRef, 'col' => 'Id'], 'pdtID' => ['__ref' => $pRef, 'col' => 'Id'],
                    'descriptionEn' => $d->definitionEn ?? '', 'descriptionPt' => $d->definitionPt ?? '',
                    'referenceDocumentGUID' => 'n/a',
                ]];
            }
        }

        // Retire the OLD subtree: PDT deprecated; its GOP rows InActive (status only).
        $ops[] = ['type' => 'deprecate', 'table' => self::PDT, 'id' => $pdt->Id, 'explanation' => "Superseded by version {$pv}.{$pr}"];
        $ops[] = ['type' => 'setStatus', 'table' => self::GOP, 'where' => ['pdtId' => $pdt->Id], 'status' => self::ST_INACTIVE];
    }

    // ============================================================ APPLY

    public function applyPlan(array $plan): array
    {
        if (empty($plan['ops'])) {
            return ['applied' => false, 'message' => $plan['summary'][0] ?? 'Nothing to apply.', 'summary' => $plan['summary'] ?? []];
        }

        $backupPath = $this->writeBackup($plan);
        $created = [];

        DB::transaction(function () use ($plan, &$created) {
            $refs = [];
            foreach ($plan['ops'] as $op) {
                switch ($op['type']) {
                    case 'insert':
                        $id = DB::table($op['table'])->insertGetId($this->resolveRefs($op['values'], $refs), 'Id');
                        if (!empty($op['ref'])) {
                            $refs[$op['ref']] = DB::table($op['table'])->where('Id', $id)->first();
                        }
                        $created[] = "{$op['table']}#{$id}";
                        break;

                    case 'repoint':
                        $q = DB::table($op['table']);
                        foreach ($op['where'] as $col => $val) {
                            is_array($val) ? $q->whereIn($col, $val) : $q->where($col, $val);
                        }
                        $q->update($this->resolveRefs($op['set'], $refs));
                        break;

                    case 'setStatus':
                        $q = DB::table($op['table']);
                        foreach ($op['where'] as $col => $val) {
                            is_array($val) ? $q->whereIn($col, $val) : $q->where($col, $val);
                        }
                        $q->update(['status' => $op['status']]);
                        break;

                    case 'deprecate':
                        $upd = [
                            'status'                  => self::ST_INACTIVE,
                            'depreciationDate'        => Carbon::today()->toDateString(),
                            'depreciationExplanation' => $op['explanation'] ?? 'Superseded',
                        ];
                        if (isset($op['replacing']) && in_array($op['table'], [self::DICT, self::GOP], true)) {
                            $upd['listOfReplacingProperties'] = $op['replacing'];
                        }
                        DB::table($op['table'])->where('Id', $op['id'])->update($upd);
                        break;
                }
            }
        });

        return [
            'applied' => true,
            'created' => $created,
            'backup'  => basename($backupPath),
            'summary' => $plan['summary'],
            'message' => 'Applied: ' . count($created) . ' new row(s) created.',
        ];
    }

    private function resolveRefs(array $values, array $refs): array
    {
        foreach ($values as $k => $v) {
            if (is_array($v) && isset($v['__ref'])) {
                $values[$k] = $refs[$v['__ref']]->{$v['col']};
            }
        }
        return $values;
    }

    // ============================================================ STATUS recompute

    public function recomputeStatuses(bool $apply = false): array
    {
        $changes = [];

        $pdts = DB::table(self::PDT)->get();
        foreach ($pdts->groupBy('GUID') as $rows) {
            $nonPreview = $rows->reject(fn($r) => $r->status === self::ST_PREVIEW)
                ->sortBy([['versionNumber', 'desc'], ['revisionNumber', 'desc']])->values();
            $headId = optional($nonPreview->first())->Id;
            foreach ($rows as $r) {
                if ($r->status === self::ST_PREVIEW) {
                    continue;
                }
                $this->diffStatus($changes, self::PDT, $r, $r->Id === $headId ? self::ST_ACTIVE : self::ST_INACTIVE);
            }
        }
        if ($apply) {
            $this->writeStatusChanges(array_filter($changes, fn($c) => $c['table'] === self::PDT));
        }
        $pdtStatus = DB::table(self::PDT)->pluck('status', 'Id');

        foreach (DB::table(self::GOP)->get() as $g) {
            if ($g->status === self::ST_PREVIEW) {
                continue;
            }
            $this->diffStatus($changes, self::GOP, $g, $pdtStatus[$g->pdtId] ?? self::ST_INACTIVE);
        }

        $ctxByProp = DB::table(self::PROP)->select('propertyId', 'pdtID')->get()->groupBy('propertyId');
        foreach (DB::table(self::DICT)->get() as $d) {
            if ($d->status === self::ST_PREVIEW) {
                continue;
            }
            $ctx = $ctxByProp->get($d->Id);
            $statuses = collect($ctx ? $ctx->pluck('pdtID')->all() : [])->map(fn($id) => $pdtStatus[$id] ?? self::ST_INACTIVE);
            $want = $statuses->contains(self::ST_ACTIVE) ? self::ST_ACTIVE
                : ($statuses->contains(self::ST_PREVIEW) ? self::ST_PREVIEW : self::ST_INACTIVE);
            $this->diffStatus($changes, self::DICT, $d, $want);
        }

        // Construction objects: GUID is the PRIMARY KEY, so each CO is a single row (no
        // shared-GUID version lineage, unlike PDT/GOP/dict). Status is purely the referrer
        // rule: Active if any Active PDT references the CO's GUID (via
        // productdatatemplates.constructionObjectGUID), Preview if only Preview PDTs
        // reference it, else InActive. Preview-status CO rows (drafts) are left as-is.
        $pdtByCoGuid = DB::table(self::PDT)->select('Id', 'constructionObjectGUID')
            ->whereNotNull('constructionObjectGUID')->get()->groupBy('constructionObjectGUID');
        foreach (DB::table(self::CO)->get() as $co) {
            if ($co->status === self::ST_PREVIEW) {
                continue; // draft CO, leave as-is
            }
            $refStatuses = ($pdtByCoGuid->get($co->GUID) ?? collect())
                ->map(fn($p) => $pdtStatus[$p->Id] ?? self::ST_INACTIVE);
            $want = $refStatuses->contains(self::ST_ACTIVE) ? self::ST_ACTIVE
                : ($refStatuses->contains(self::ST_PREVIEW) ? self::ST_PREVIEW : self::ST_INACTIVE);
            $this->diffStatus($changes, self::CO, $co, $want, 'GUID');
        }

        if ($apply) {
            $this->writeStatusChanges(array_filter($changes, fn($c) => $c['table'] !== self::PDT));
        }

        return $changes;
    }

    private function diffStatus(array &$changes, string $table, $row, string $want, string $keyCol = 'Id'): void
    {
        if ($row->status !== $want) {
            $changes[] = ['table' => $table, 'id' => $row->{$keyCol}, 'from' => $row->status, 'to' => $want, 'keyCol' => $keyCol];
        }
    }

    private function writeStatusChanges(array $changes): void
    {
        foreach ($changes as $c) {
            DB::table($c['table'])->where($c['keyCol'] ?? 'Id', $c['id'])->update(['status' => $c['to']]);
        }
    }

    // ============================================================ row builders

    /** New VERSION row: copy source, drop Id, apply overrides, set version/dates/status. */
    private function cloneForNewVersion(array $source, array $newValues, array $allowedOverrides, int $version, int $revision): array
    {
        unset($source['Id']);
        foreach ($newValues as $f => $v) {
            if (in_array($f, $allowedOverrides, true)) {
                $source[$f] = $v;
            }
        }
        $today = Carbon::today()->toDateString();
        $source['versionNumber'] = $version;
        $source['revisionNumber'] = $revision;
        if (array_key_exists('status', $source)) {
            $source['status'] = self::ST_ACTIVE;
        }
        foreach (['dateOfVersion', 'dateOfRevision', 'dateOfLastChange', 'updated_at', 'created_at'] as $d) {
            if (array_key_exists($d, $source)) {
                $source[$d] = $today;
            }
        }
        foreach (['depreciationDate', 'depreciationExplanation'] as $d) {
            if (array_key_exists($d, $source)) {
                $source[$d] = null;
            }
        }
        return $source;
    }

    /** SNAPSHOT clone: copy source, drop Id, KEEP version/revision/dates, status Active. */
    private function cloneSnapshot(array $source, array $overrides): array
    {
        unset($source['Id']);
        foreach ($overrides as $k => $v) {
            $source[$k] = $v;
        }
        if (array_key_exists('status', $source)) {
            $source['status'] = self::ST_ACTIVE;
        }
        foreach (['depreciationDate', 'depreciationExplanation'] as $d) {
            if (array_key_exists($d, $source)) {
                $source[$d] = null;
            }
        }
        return $source;
    }

    /** Fresh GOP (from-existing attrs or scratch) with a NEW GUID, v1.0, Active. */
    private function cloneSnapshotFresh(array $base, array $attrs, string $newGuid): array
    {
        unset($base['Id']);
        foreach ($attrs as $k => $v) {
            $base[$k] = $v;
        }
        $today = Carbon::today()->toDateString();
        $base['GUID'] = $newGuid;
        $base['versionNumber'] = 1;
        $base['revisionNumber'] = 0;
        $base['status'] = self::ST_ACTIVE;
        $base['gopNameEn'] = $base['gopNameEn'] ?? 'New group';
        $base['gopNamePt'] = $base['gopNamePt'] ?? 'Novo grupo';
        $base['dateOfRevision'] = $today;
        $base['dateOfVersion'] = $today;
        foreach (['dateOfCreation', 'dateofActivation', 'dateOfLastChange', 'updated_at', 'created_at'] as $d) {
            $base[$d] = $today;
        }
        foreach (['depreciationDate', 'depreciationExplanation', 'listOfReplacedProperties', 'listOfReplacingProperties'] as $d) {
            $base[$d] = null;
        }
        return $base;
    }

    /**
     * Column values for a brand-new dictionary property (fresh lineage): v1.0, Active,
     * dates today. Only name/definition/attribute fields are taken from $vals.
     */
    public function buildNewDictValues(array $vals, string $guid): array
    {
        $today = Carbon::today()->toDateString();
        $row = [
            'GUID' => $guid, 'versionNumber' => 1, 'revisionNumber' => 0, 'status' => self::ST_ACTIVE,
            'nameEn' => $vals['nameEn'] ?? 'New property', 'namePt' => $vals['namePt'] ?? 'Nova propriedade',
            'definitionEn' => $vals['definitionEn'] ?? '', 'definitionPt' => $vals['definitionPt'] ?? '',
            'dateOfRevision' => $today, 'dateOfVersion' => $today, 'dateOfCreation' => $today,
            'created_at' => $today, 'updated_at' => $today,
        ];
        foreach (array_merge(['nameEnSc', 'namePtSc'], self::DICT_ATTRS) as $f) {
            if (array_key_exists($f, $vals)) {
                $row[$f] = $vals[$f];
            }
        }
        return $row;
    }

    private function emptyPlan(string $rootType, int $rootId, string $message): array
    {
        return ['rootType' => $rootType, 'rootId' => $rootId, 'ops' => [], 'summary' => [$message]];
    }

    private function writeBackup(array $plan): string
    {
        $touched = ['generated_at' => now()->toIso8601String(), 'plan_summary' => $plan['summary'] ?? [], 'rows' => []];
        foreach ($plan['ops'] as $op) {
            if (in_array($op['type'], ['deprecate'], true)) {
                $touched['rows'][] = ['table' => $op['table'], 'row' => DB::table($op['table'])->where('Id', $op['id'])->first()];
            }
            if (in_array($op['type'], ['setStatus', 'repoint'], true)) {
                $q = DB::table($op['table']);
                foreach ($op['where'] as $col => $val) {
                    is_array($val) ? $q->whereIn($col, $val) : $q->where($col, $val);
                }
                $touched['rows'][] = ['table' => $op['table'], 'matched' => $q->get()];
            }
        }
        $stamp = now()->format('Ymd_His');
        $path = storage_path("app/versioning_backup_{$stamp}.json");
        $i = 1;
        while (file_exists($path)) {
            $path = storage_path("app/versioning_backup_{$stamp}_{$i}.json");
            $i++;
        }
        file_put_contents($path, json_encode($touched, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        return $path;
    }
}
