<?php

namespace App\Services;

use App\Models\EntityRelationship;
use App\Models\groupofproperties;
use Illuminate\Support\Facades\DB;

/**
 * Shared EN ISO 23387:2025 R-23387-7 inheritance resolver for the USER-FACING pages
 * (pdtview, the download/review page). Returns a PDT's effective group list = its own
 * groups + the groups inherited from every IsSubtypeOf ancestor (latest active),
 * walked TRANSITIVELY (A->B->C inherits B and C) and collapsed BY GUID LINEAGE.
 *
 * This is the same rule the exporters apply (ProductdatatemplatesController::resolvePdtGroups
 * and Iso23387Exporter), centralised so the pages can't drift from the exports.
 */
class PdtInheritanceService
{
    private const MASTER_PDT_GUID = '230d9954097541b793f2a1fddb8bd0ad';

    public function __construct(private RelationshipService $relations = new RelationshipService())
    {
    }

    /**
     * Effective groups for a PDT (own + inherited, transitive, collapsed by GUID).
     * Returns an Eloquent Collection of groupofproperties rows. Nearest/self wins.
     */
    public function groups($pdt)
    {
        $merged = collect();
        $seenGuids = [];
        $append = function ($groups) use ($merged, &$seenGuids) {
            foreach ($groups as $g) {
                if ($g->GUID !== null && isset($seenGuids[$g->GUID])) continue;
                if ($g->GUID !== null) $seenGuids[$g->GUID] = true;
                $merged->push($g);
            }
        };

        $append(groupofproperties::where('pdtId', $pdt->Id)->get());

        $ancestors = $this->relations->subtypeAncestors(EntityRelationship::TYPE_PDT, $pdt->GUID);

        // Fallback: store not seeded with any subtype edge for this PDT -> behave like the
        // legacy single-master merge so pages match pre-store behaviour.
        if (empty($ancestors) && $pdt->GUID !== self::MASTER_PDT_GUID) {
            $master = DB::table('productdatatemplates')->where('GUID', self::MASTER_PDT_GUID)
                ->where('status', 'Active')->orderByRaw('versionNumber DESC, revisionNumber DESC')->first();
            if ($master) {
                $append(groupofproperties::where('pdtId', $master->Id)->get());
            }
            return $merged;
        }

        foreach ($ancestors as $aGuid) {
            $aPdt = DB::table('productdatatemplates')->where('GUID', $aGuid)->where('status', 'Active')
                ->orderByRaw('versionNumber DESC, revisionNumber DESC')->first();
            if (!$aPdt) continue;
            $append(groupofproperties::where('pdtId', $aPdt->Id)->get());
        }

        return $merged;
    }

    /**
     * Where each of a PDT's effective groups comes from, for the property origin filter.
     * Display data only: nothing here feeds an export or the API.
     *
     *   groupOrigin  [gopId => key]
     *   origins      ordered list of ['key', 'label', 'color', 'count' (properties), 'groupIds']:
     *                'own' (this template) first, then each IsSubtypeOf ancestor in chain order
     *                (key = its lineage GUID), then 'master' (the master template, however it
     *                is reached). Only origins that contribute a group are listed.
     *   total        property count over all origins
     *
     * Colours are sourceColors() over the same ordered supertype-name list the pages already
     * use for row colours and the legend, so every swatch matches its rows; own is white.
     *
     * @param  object $pdt     row with Id and GUID
     * @param  iterable|null $groups the effective groups, if already resolved (groups($pdt))
     */
    public function origins($pdt, $groups = null): array
    {
        $groups = collect($groups ?? $this->groups($pdt));
        $ownerIds = $groups->pluck('pdtId')->unique()->reject(fn($id) => (int) $id === (int) $pdt->Id)->values();
        $owners = $ownerIds->isEmpty() ? collect()
            : DB::table('productdatatemplates')->whereIn('Id', $ownerIds)->get()->keyBy('Id');

        // Property counts per group, one query.
        $counts = $groups->isEmpty() ? collect()
            : DB::table('properties')->whereIn('gopID', $groups->pluck('Id'))
                ->selectRaw('gopID, COUNT(*) as n')->groupBy('gopID')->pluck('n', 'gopID');

        $groupOrigin = [];
        $byKey = [];
        $sourceNames = [];     // the ordered supertype-name list behind sourceColors()
        foreach ($groups as $g) {
            if ((int) $g->pdtId === (int) $pdt->Id) {
                $key = 'own';
                $label = \App\Support\Lang::t('Este modelo');
                $name = null;
            } else {
                $owner = $owners->get($g->pdtId);
                $name = $owner ? ($owner->pdtNamePt ?: $owner->pdtNameEn) : 'Supertipo';
                $sourceNames[] = $name;
                if ($owner && $owner->GUID === self::MASTER_PDT_GUID) {
                    $key = 'master';
                    $label = \App\Support\Lang::t('Modelo mestre');
                } else {
                    $key = $owner ? (string) $owner->GUID : 'pdt-' . $g->pdtId;
                    $label = $owner ? \App\Support\Lang::f($owner, 'pdtName') : \App\Support\Lang::pick('Supertipo', 'Supertype');
                }
            }
            $groupOrigin[$g->Id] = $key;
            $byKey[$key] ??= ['key' => $key, 'label' => $label, 'name' => $name, 'count' => 0, 'groupIds' => []];
            $byKey[$key]['groupIds'][] = (int) $g->Id;
            $byKey[$key]['count'] += (int) ($counts[$g->Id] ?? 0);
        }

        $colors = self::sourceColors(array_values(array_unique($sourceNames)));
        $ordered = [];
        foreach (array_merge(['own'], array_diff(array_keys($byKey), ['own', 'master']), ['master']) as $key) {
            if (!isset($byKey[$key])) continue;
            $o = $byKey[$key];
            $o['color'] = $key === 'own' ? '#ffffff' : ($colors[$o['name']] ?? '#ffffff');
            unset($o['name']);
            $ordered[] = $o;
        }

        return [
            'groupOrigin' => $groupOrigin,
            'origins' => $ordered,
            'total' => array_sum(array_column($ordered, 'count')),
        ];
    }

    /**
     * The inherited groups of a PDT with their properties (name, data type, unit), for the
     * read-only "inherited" section of the admin editors. [['group' => row, 'origin' => key,
     * 'properties' => [row...]], ...] in origins() order.
     */
    public function inheritedGroupsWithProperties($pdt, array $origins): array
    {
        $out = [];
        foreach ($origins['origins'] as $o) {
            if ($o['key'] === 'own') continue;
            $gops = DB::table('groupofproperties')->whereIn('Id', $o['groupIds'])->orderBy('Id')->get();
            $props = DB::table('properties as p')
                ->leftJoin('propertiesdatadictionaries as d', 'd.Id', '=', 'p.propertyId')
                ->whereIn('p.gopID', $o['groupIds'])
                ->select('p.Id', 'p.gopID', 'd.nameEn', 'd.namePt', 'd.dataType', 'd.units')
                ->orderBy('p.Id')->get()->groupBy('gopID');
            foreach ($gops as $g) {
                $out[] = ['group' => $g, 'origin' => $o, 'properties' => $props->get($g->Id, collect())->all()];
            }
        }
        return $out;
    }

    /** The gopID set of a PDT's effective groups (handy for filtering property rows). */
    public function groupIds($pdt): array
    {
        return $this->groups($pdt)->pluck('Id')->all();
    }

    /**
     * Assign a distinct VERY LIGHT shade to each inherited-source name (for row colour-coding
     * + legend). Stable by order; cycles the palette if there are more sources than colours.
     */
    public static function sourceColors(array $names): array
    {
        $palette = ['#eef2ff', '#ecfdf5', '#fef3c7', '#fce7f3', '#eff6ff', '#f3e8ff', '#fef2f2', '#f0fdf4', '#fdf4ff', '#fffbeb'];
        $map = [];
        $i = 0;
        foreach ($names as $n) {
            $map[$n] = $palette[$i % count($palette)];
            $i++;
        }
        return $map;
    }
}
