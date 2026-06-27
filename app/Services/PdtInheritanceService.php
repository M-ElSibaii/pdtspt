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
