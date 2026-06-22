<?php

namespace App\Http\Controllers;

use App\Services\GuidGenerator;
use App\Services\PreviewService;
use App\Services\SchemaAttributeService;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Admin Preview workflow UI. A PDT with status = 'Preview' is a free-editing draft:
 * every edit writes in place via {@see PreviewService} (no versioning/cascade). Hard
 * delete and publish each run through a plan -> confirm-summary -> apply pattern,
 * mirroring the dedupe tool's AJAX conventions.
 *
 * All routes are admin-gated in routes/web.php. Each mutating action re-asserts the
 * PDT is in Preview inside the service, so a stale page cannot edit an Active PDT.
 */
class PreviewWorkflowController extends Controller
{
    private const DICT = 'propertiesdatadictionaries';
    private const GOP  = 'groupofproperties';
    private const PROP = 'properties';
    private const PDT  = 'productdatatemplates';

    /** List existing Preview drafts + a create-draft form. */
    public function drafts()
    {
        $drafts = DB::table(self::PDT)->where('status', 'Preview')
            ->orderByDesc('Id')->get();

        return view('admin.previews-index', [
            'drafts' => $drafts,
            'pdtFields' => app(SchemaAttributeService::class)->describe(self::PDT),
        ]);
    }

    /**
     * Create a Preview PDT draft from the full attribute form. System/lineage fields are
     * auto-managed; the user-editable attributes come from the form. Mandatory enforced.
     * Empty enum/optional values are sanitised to valid defaults (the old empty-string
     * category was an invalid enum value and broke the insert).
     */
    public function createDraft(Request $request, SchemaAttributeService $schema)
    {
        $attrs = (array) $request->input('pdt', []);
        $set = array_intersect_key($attrs, array_flip($schema->editable(self::PDT)));

        $missing = $schema->missingMandatory(self::PDT, $set);
        if ($missing) {
            return redirect()->back()->withInput()->with('error', 'Missing mandatory: ' . implode(', ', $missing));
        }

        $today = Carbon::today()->toDateString();
        $row = array_merge([
            'GUID' => GuidGenerator::generateUnique(),
            'referenceDocumentGUID' => 'n/a',
            'constructionObjectGUID' => null,
            'descriptionEn' => '', 'descriptionPt' => '',
            'category' => 'Construção',
            'status' => 'Preview', 'versionNumber' => 1, 'revisionNumber' => 0,
            'dateOfVersion' => $today, 'dateOfRevision' => $today, 'created_at' => $today, 'updated_at' => $today,
            'depreciationExplanation' => null, 'depreciationDate' => null,
        ], $set);

        // Sanitise empties so we never write an invalid enum / FK sentinel.
        if (empty($row['category'])) {
            $row['category'] = 'Construção';
        }
        if (empty($row['referenceDocumentGUID'])) {
            $row['referenceDocumentGUID'] = 'n/a';
        }
        if (empty($row['constructionObjectGUID'])) {
            $row['constructionObjectGUID'] = null;
        }

        try {
            $id = DB::table(self::PDT)->insertGetId($row, 'Id');
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Could not create draft: ' . $e->getMessage());
        }

        return redirect()->route('admin.previews.editor', ['pdt' => $id]);
    }

    /** The Preview editor: PDT + its GOPs + context rows (joined to dictionary). */
    public function editor(int $pdt, PreviewService $service, SchemaAttributeService $schema)
    {
        try {
            $service->assertPreviewPdt($pdt);
        } catch (\Throwable $e) {
            return redirect()->route('admin.previews')->with('error', $e->getMessage());
        }

        $pdtRow = DB::table(self::PDT)->where('Id', $pdt)->first();
        $gops = DB::table(self::GOP)->where('pdtId', $pdt)->orderBy('Id')->get();

        // Context rows for this PDT, joined to their dictionary definition.
        $context = DB::table(self::PROP . ' as p')
            ->leftJoin(self::DICT . ' as d', 'd.Id', '=', 'p.propertyId')
            ->where('p.pdtID', $pdt)
            ->select(
                'p.Id',
                'p.gopID',
                'p.propertyId',
                'p.GUID',
                'p.descriptionEn',
                'p.descriptionPt',
                'p.referenceDocumentGUID',
                'p.visualRepresentation',
                'd.nameEn as dictNameEn',
                'd.namePt as dictNamePt',
                'd.definitionEn as dictDefEn',
                'd.definitionPt as dictDefPt',
                'd.dataType as dictDataType',
                'd.units as dictUnits',
                'd.status as dictStatus',
                'd.versionNumber as dictVersion',
                'd.revisionNumber as dictRevision'
            )
            ->orderBy('p.Id')
            ->get();

        // Mark whether each context's dict row is shared with another PDT (=> editing forks).
        foreach ($context as $c) {
            $c->shared = $c->propertyId
                ? DB::table(self::PROP)->where('propertyId', $c->propertyId)
                ->where('pdtID', '<>', $pdt)->exists()
                : false;
        }
        $contextByGop = $context->groupBy('gopID');

        // Full dictionary rows for every referenced property (for the full-attribute
        // definition editor), keyed by dict Id.
        $dictIds = $context->pluck('propertyId')->filter()->unique()->values();
        $dictRows = $dictIds->isEmpty() ? collect()
            : DB::table(self::DICT)->whereIn('Id', $dictIds)->get()->keyBy('Id');

        return view('admin.preview-editor', [
            'pdt' => $pdtRow,
            'gops' => $gops,
            'contextByGop' => $contextByGop,
            'dictRows' => $dictRows,
            'pdtFields'  => $schema->describe(self::PDT),
            'gopFields'  => $schema->describe(self::GOP),
            'ctxFields'  => $schema->describe(self::PROP),
            'dictFields' => $schema->describe(self::DICT),
            'dictEnums'  => \App\Services\BsddEnums::dictionaryFieldEnums(),
        ]);
    }

    // ============================================================ free-edit AJAX

    public function editPdt(Request $request, int $pdt, PreviewService $service)
    {
        return $this->guarded(fn() => $service->editPdtAttributes($pdt, (array) $request->input('attrs', [])));
    }

    public function editGop(Request $request, int $pdt, PreviewService $service)
    {
        $gopId = (int) $request->input('gopId');
        return $this->guarded(fn() => $service->editGopAttributes($gopId, (array) $request->input('attrs', [])));
    }

    public function addGop(Request $request, int $pdt, PreviewService $service, SchemaAttributeService $schema)
    {
        return $this->guarded(function () use ($request, $pdt, $service, $schema) {
            $attrs = (array) $request->input('attrs', []);
            $missing = $schema->missingMandatory('groupofproperties', $attrs);
            if ($missing) {
                throw new \RuntimeException('Missing mandatory: ' . implode(', ', $missing));
            }
            $id = $service->addGop($pdt, $attrs, $request->input('guid'));
            return ['gopId' => $id];
        });
    }

    /** Distinct existing GOP names + latest definition, for the name-dropdown shortcut. */
    public function gopSuggestions(int $pdt, PreviewService $service)
    {
        return response()->json(['ok' => true, 'results' => $service->gopNameSuggestions()]);
    }

    public function removeGop(Request $request, int $pdt, PreviewService $service)
    {
        return $this->guarded(fn() => $service->removeGop((int) $request->input('gopId')));
    }

    public function editContext(Request $request, int $pdt, PreviewService $service)
    {
        $contextId = (int) $request->input('contextId');
        return $this->guarded(fn() => $service->editContext($contextId, (array) $request->input('attrs', [])));
    }

    public function removeContext(Request $request, int $pdt, PreviewService $service)
    {
        return $this->guarded(fn() => $service->removeProperty((int) $request->input('contextId')));
    }

    /** Edit a property's dictionary definition — forks automatically if shared. */
    public function editProperty(Request $request, int $pdt, PreviewService $service)
    {
        $contextId = (int) $request->input('contextId');
        return $this->guarded(fn() => $service->editPreviewProperty($contextId, (array) $request->input('values', [])));
    }

    public function addExistingProperty(Request $request, int $pdt, PreviewService $service)
    {
        $gopId = (int) $request->input('gopId');
        $dictId = (int) $request->input('dictId');
        return $this->guarded(fn() => ['contextId' => $service->addExistingProperty($gopId, $dictId)]);
    }

    public function addNewProperty(Request $request, int $pdt, PreviewService $service, SchemaAttributeService $schema)
    {
        $gopId = (int) $request->input('gopId');
        $values = (array) $request->input('values', []);
        return $this->guarded(function () use ($service, $schema, $gopId, $values, $request) {
            $missing = $schema->missingMandatory('propertiesdatadictionaries', $values);
            if ($missing) {
                throw new \RuntimeException('Missing mandatory dictionary attributes: ' . implode(', ', $missing));
            }
            return $service->addNewProperty($gopId, $values, $request->input('guid'));
        });
    }

    // ============================================================ hard delete

    public function deletePlan(int $pdt, PreviewService $service)
    {
        return $this->guarded(fn() => $service->planPreviewDelete($pdt));
    }

    public function deleteApply(int $pdt, PreviewService $service)
    {
        return $this->guarded(fn() => $service->applyPreviewDelete($pdt));
    }

    // ============================================================ publish

    public function publishPlan(int $pdt, PreviewService $service)
    {
        return $this->guarded(fn() => $service->planPublish($pdt));
    }

    public function publishApply(Request $request, int $pdt, PreviewService $service)
    {
        $decisions = (array) $request->input('decisions', []);
        // Keys arrive as strings from JSON; PreviewService indexes by contextId int.
        $decisions = collect($decisions)->mapWithKeys(fn($v, $k) => [(int) $k => (string) $v])->all();
        return $this->guarded(fn() => $service->applyPublish($pdt, $decisions));
    }

    // ============================================================ helper

    /**
     * Run a mutation closure and normalise to the {ok:true,...} / {ok:false,error}
     * JSON shape the front-end expects, matching the dedupe tool.
     */
    private function guarded(\Closure $fn)
    {
        try {
            $payload = $fn();
            $body = ['ok' => true];
            if (is_array($payload)) {
                $body += $payload;
            } elseif ($payload !== null) {
                $body['result'] = $payload;
            }
            return response()->json($body);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
