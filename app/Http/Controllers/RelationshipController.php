<?php

namespace App\Http\Controllers;

use App\Models\EntityRelationship;
use App\Services\RelationshipException;
use App\Services\RelationshipService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Generic JSON CRUD + search powering the reusable relation-editor component
 * (EN ISO 23387:2025 R-23387-7). Entity-agnostic: works for pdt / gop / property /
 * objecttype via one metadata map. All identity is GUID lineage.
 */
class RelationshipController extends Controller
{
    public function __construct(private RelationshipService $relations)
    {
    }

    /** entity-type token => [table, guidCol, [nameCols...]]. objecttype = constructionobjects. */
    private const META = [
        EntityRelationship::TYPE_PDT        => ['productdatatemplates', 'GUID', ['pdtNamePt', 'pdtNameEn']],
        EntityRelationship::TYPE_GOP        => ['groupofproperties', 'GUID', ['gopNamePt', 'gopNameEn']],
        EntityRelationship::TYPE_PROPERTY   => ['propertiesdatadictionaries', 'GUID', ['namePt', 'nameEn']],
        EntityRelationship::TYPE_OBJECTTYPE => ['constructionobjects', 'GUID', ['constructionObjectNamePt', 'constructionObjectNameEn']],
    ];

    /** Search target entities of a kind by name; latest-active per GUID lineage. */
    public function search(Request $request, string $entityType)
    {
        if (!isset(self::META[$entityType])) {
            return response()->json(['error' => 'Unknown entity type'], 422);
        }
        [$table, $guidCol, $nameCols] = self::META[$entityType];
        $q = trim((string) $request->query('q', ''));

        $rows = DB::table($table)
            ->when(in_array('status', $this->columns($table), true), fn($qb) => $qb->where('status', 'Active'))
            ->when($q !== '', function ($qb) use ($q, $nameCols) {
                $qb->where(function ($w) use ($q, $nameCols) {
                    foreach ($nameCols as $col) $w->orWhere($col, 'like', "%{$q}%");
                });
            })
            ->orderByRaw('versionNumber DESC, revisionNumber DESC')
            ->limit(200)
            ->get();

        // Dedup to one (latest) per GUID lineage.
        $seen = [];
        $out = [];
        foreach ($rows as $r) {
            $guid = $r->$guidCol;
            if (isset($seen[$guid])) continue;
            $seen[$guid] = true;
            $out[] = ['guid' => $guid, 'name' => $this->displayName($entityType, $r)];
            if (count($out) >= 25) break;
        }
        return response()->json(['results' => $out]);
    }

    /** List live relations originating from a source lineage, with resolved target names. */
    public function index(string $entityType, string $guid)
    {
        $rels = $this->relations->relationsFrom($entityType, $guid);
        return response()->json([
            'relations' => $rels->map(fn($r) => [
                'id'           => $r->id,
                'relationType' => $r->relationType,
                'targetType'   => $r->targetEntityType,
                'targetGuid'   => $r->targetGuid,
                'targetName'   => $this->resolveName($r->targetEntityType, $r->targetGuid),
                'position'     => $r->position,
            ])->values(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sourceEntityType' => 'required|string',
            'sourceGuid'       => 'required|string',
            'relationType'     => 'required|string',
            'targetEntityType' => 'required|string',
            'targetGuid'       => 'required|string',
            'position'         => 'nullable|integer',
        ]);

        try {
            $rel = $this->relations->relate(
                $data['sourceEntityType'], $data['sourceGuid'], $data['relationType'],
                $data['targetEntityType'], $data['targetGuid'],
                null, null, $data['position'] ?? null
            );
        } catch (RelationshipException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json([
            'ok'       => true,
            'relation' => [
                'id'           => $rel->id,
                'relationType' => $rel->relationType,
                'targetType'   => $rel->targetEntityType,
                'targetGuid'   => $rel->targetGuid,
                'targetName'   => $this->resolveName($rel->targetEntityType, $rel->targetGuid),
                'position'     => $rel->position,
            ],
        ]);
    }

    public function destroy(int $id)
    {
        $this->relations->unrelate($id);
        return response()->json(['ok' => true]);
    }

    /** Persist HasPart ordering: payload { ids: [relId, ...] } in display order. */
    public function reorder(Request $request)
    {
        $ids = $request->input('ids', []);
        foreach (array_values((array) $ids) as $pos => $id) {
            EntityRelationship::where('id', (int) $id)->update(['position' => $pos]);
        }
        return response()->json(['ok' => true]);
    }

    // ------------------------------------------------------------- helpers

    private function resolveName(string $entityType, string $guid): string
    {
        if (!isset(self::META[$entityType])) return $guid;
        [$table, $guidCol] = self::META[$entityType];
        $row = DB::table($table)
            ->where($guidCol, $guid)
            ->when(in_array('status', $this->columns($table), true), fn($qb) => $qb->orderByRaw("FIELD(status,'Active') DESC"))
            ->orderByRaw('versionNumber DESC, revisionNumber DESC')
            ->first();
        return $row ? $this->displayName($entityType, $row) : $guid;
    }

    /** Bilingual label "PT / EN" so the picker always shows both languages. */
    private function displayName(string $entityType, $row): string
    {
        // META name columns are ordered [PT, EN].
        [, , $nameCols] = self::META[$entityType];
        $pt = $nameCols[0] ?? null;
        $en = $nameCols[1] ?? null;
        $ptVal = $pt && !empty($row->$pt) ? $row->$pt : null;
        $enVal = $en && !empty($row->$en) ? $row->$en : null;

        if ($ptVal && $enVal) return $ptVal . ' / ' . $enVal;
        return $ptVal ?: ($enVal ?: '(unnamed)');
    }

    /** Memoized column list per table (so we only add status filters where the column exists). */
    private array $colCache = [];
    private function columns(string $table): array
    {
        return $this->colCache[$table] ??= \Illuminate\Support\Facades\Schema::getColumnListing($table);
    }
}
