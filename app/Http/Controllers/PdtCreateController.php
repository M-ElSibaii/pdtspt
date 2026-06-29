<?php

namespace App\Http\Controllers;

use App\Services\GuidGenerator;
use App\Services\SchemaAttributeService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * CREATE mode: a new PDT begins from a Construction Object (select an existing one or
 * create a new one with full attributes), then the PDT itself with full attributes. Both
 * land at status = Preview (a draft) and the admin continues in the Preview editor to add
 * GOPs and properties, then publishes. Mandatory attributes are enforced server-side via
 * {@see SchemaAttributeService} (mirrored by the form's inline blocking); system/lineage
 * fields (GUID, version, dates, status) are auto-managed, never user-supplied.
 */
class PdtCreateController extends Controller
{
    private const CO  = 'constructionobjects';
    private const PDT = 'productdatatemplates';

    public function create(SchemaAttributeService $schema)
    {
        return view('admin.create-pdt', [
            'coFields'    => $schema->describe(self::CO),
            'pdtFields'   => $schema->describe(self::PDT),
            'existingCos' => DB::table(self::CO)->where('status', 'Active')
                ->orderBy('constructionObjectNameEn')
                ->get(['GUID', 'constructionObjectNameEn', 'constructionObjectNamePt']),
        ]);
    }

    public function store(Request $request, SchemaAttributeService $schema)
    {
        $coMode = $request->input('co_mode', 'existing');
        $coInput = (array) $request->input('co', []);
        $pdtInput = (array) $request->input('pdt', []);

        // ---- validate mandatory (server-side guard) ----
        $errors = [];
        if ($coMode === 'new') {
            foreach ($schema->missingMandatory(self::CO, $coInput) as $f) {
                $errors[] = "Object Type: '{$f}' is required.";
            }
        } else {
            $coGuid = $request->input('constructionObjectGUID');
            if (!$coGuid || !DB::table(self::CO)->where('GUID', $coGuid)->exists()) {
                $errors[] = 'Please select an existing Object Type (or create a new one).';
            }
        }
        foreach ($schema->missingMandatory(self::PDT, $pdtInput) as $f) {
            $errors[] = "PDT: '{$f}' is required.";
        }
        if ($errors) {
            return back()->withInput()->with('createErrors', $errors);
        }

        $today = Carbon::today()->toDateString();

        $newPdtId = DB::transaction(function () use ($coMode, $coInput, $pdtInput, $request, $schema, $today) {
            // ---- construction object ----
            if ($coMode === 'new') {
                $coGuid = GuidGenerator::generateUnique();
                $coVals = array_intersect_key($coInput, array_flip($schema->editable(self::CO)));
                $coVals['referenceDocumentGUID'] = $coVals['referenceDocumentGUID'] ?? null ?: null;
                DB::table(self::CO)->insert(array_merge($coVals, [
                    'GUID' => $coGuid, 'status' => 'Preview',
                    'versionNumber' => 1, 'revisionNumber' => 0,
                    'dateOfRevision' => $today, 'dateOfVersion' => $today,
                    'created_at' => $today, 'updated_at' => $today,
                ]));
            } else {
                $coGuid = $request->input('constructionObjectGUID');
            }

            // ---- PDT (Preview draft) ----
            $pdtVals = array_intersect_key($pdtInput, array_flip($schema->editable(self::PDT)));
            $pdtVals['referenceDocumentGUID'] = ($pdtVals['referenceDocumentGUID'] ?? '') ?: 'n/a';
            $pdtVals['category'] = ($pdtVals['category'] ?? '') ?: 'Construção';
            return DB::table(self::PDT)->insertGetId(array_merge($pdtVals, [
                'GUID' => GuidGenerator::generateUnique(),
                'constructionObjectGUID' => $coGuid,
                'status' => 'Preview', 'versionNumber' => 1, 'revisionNumber' => 0,
                'descriptionEn' => $pdtVals['descriptionEn'] ?? '', 'descriptionPt' => $pdtVals['descriptionPt'] ?? '',
                'dateOfRevision' => $today, 'dateOfVersion' => $today,
                'created_at' => $today, 'updated_at' => $today,
                'depreciationExplanation' => null, 'depreciationDate' => null,
            ]), 'Id');
        });

        return redirect()->route('admin.previews.editor', ['pdt' => $newPdtId])
            ->with('success', 'Draft PDT created. Add groups and properties, then publish.');
    }
}
