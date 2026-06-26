<?php

namespace App\Http\Controllers;

use App\Models\PropertyDependency;
use App\Services\PropertyDependencyService;
use App\Services\RelationshipException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * JSON CRUD for property dependencies (EN ISO 23387:2025 R-23387-8), powering the
 * dependency editor. Target search reuses RelationshipController@search('property').
 */
class PropertyDependencyController extends Controller
{
    public function __construct(private PropertyDependencyService $deps)
    {
    }

    /** List a property's direct dependencies (+ resolved target names) and the nested chain. */
    public function index(string $guid)
    {
        $rows = $this->deps->dependenciesFor($guid)->map(fn($d) => [
            'id'         => $d->id,
            'kind'       => $d->dependencyKind,
            'expression' => $d->expression,
            'targets'    => $d->targets->map(fn($t) => [
                'guid'        => $t->targetPropertyGuid,
                'name'        => $this->propertyName($t->targetPropertyGuid),
                'isPreferred' => (bool) $t->isPreferred,
                'position'    => $t->position,
            ])->values(),
        ])->values();

        return response()->json([
            'dependencies' => $rows,
            'resolved'     => $this->deps->resolveChain($guid), // nested view (4.4.6)
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'sourcePropertyGuid'       => 'required|string',
            'dependencyKind'           => 'required|string',
            'expression'               => 'nullable|string',
            'note'                     => 'nullable|string',
            'targets'                  => 'required|array|min:1',
            'targets.*.guid'           => 'required|string',
            'targets.*.isPreferred'    => 'nullable|boolean',
            'targets.*.position'       => 'nullable|integer',
        ]);

        try {
            $dep = $this->deps->addDependency(
                $data['sourcePropertyGuid'],
                $data['dependencyKind'],
                $data['targets'],
                $data['expression'] ?? null,
                $data['note'] ?? null
            );
        } catch (RelationshipException $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }

        return response()->json(['ok' => true, 'id' => $dep->id]);
    }

    public function destroy(int $id)
    {
        $this->deps->removeDependency($id);
        return response()->json(['ok' => true]);
    }

    private function propertyName(string $guid): string
    {
        $r = DB::table('propertiesdatadictionaries')->where('GUID', $guid)
            ->orderByRaw("FIELD(status,'Active') DESC")->orderByRaw('versionNumber DESC, revisionNumber DESC')->first();
        if (!$r) return $guid;
        $pt = $r->namePt ?? null;
        $en = $r->nameEn ?? null;
        return ($pt && $en) ? "$pt / $en" : ($pt ?: ($en ?: '(unnamed)'));
    }
}
