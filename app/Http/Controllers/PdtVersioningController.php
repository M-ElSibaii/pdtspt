<?php

namespace App\Http\Controllers;

use App\Services\VersioningService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Staged "create a new version" editor for ACTIVE PDTs. Editing is a plan-then-apply
 * flow: the browser stages every change locally (zero DB writes), posts the net diff to
 * {@see preview()} for a read-only plan/summary, then a single {@see commit()} runs
 * {@see VersioningService::planPdtVersion()} -> {@see VersioningService::applyPlan()}
 * inside one transaction (with a JSON backup) — one new version per session.
 */
class PdtVersioningController extends Controller
{
    private const DICT = 'propertiesdatadictionaries';
    private const GOP  = 'groupofproperties';
    private const PROP = 'properties';
    private const PDT  = 'productdatatemplates';

    /** Pre-filled staged editor for the Active PDT (its current head). */
    public function editor(int $pdt, VersioningService $service, \App\Services\SchemaAttributeService $schema)
    {
        $pdtRow = DB::table(self::PDT)->where('Id', $pdt)->first();
        if (!$pdtRow) {
            abort(404);
        }
        if ($pdtRow->status !== VersioningService::ST_ACTIVE) {
            return redirect()->route('dashboard')->with('error', "PDT Id {$pdt} is '{$pdtRow->status}', not Active — only Active versions can be versioned.");
        }

        $gops = DB::table(self::GOP)->where('pdtId', $pdt)->orderBy('Id')->get();
        $context = DB::table(self::PROP . ' as p')
            ->leftJoin(self::DICT . ' as d', 'd.Id', '=', 'p.propertyId')
            ->where('p.pdtID', $pdt)
            ->select(
                'p.Id', 'p.gopID', 'p.propertyId', 'p.descriptionEn', 'p.descriptionPt',
                'p.visualRepresentation', 'p.referenceDocumentGUID', 'p.GUID',
                'd.nameEn as dictNameEn', 'd.namePt as dictNamePt',
                'd.nameEnSc as dictNameEnSc', 'd.namePtSc as dictNamePtSc',
                'd.versionNumber as dictVersion', 'd.revisionNumber as dictRevision'
            )
            ->orderBy('p.Id')->get();

        $dictIds = $context->pluck('propertyId')->filter()->unique()->values();
        $dictRows = $dictIds->isEmpty() ? collect() : DB::table(self::DICT)->whereIn('Id', $dictIds)->get()->keyBy('Id');

        return view('admin.pdt-new-version', [
            'pdt' => $pdtRow,
            'gops' => $gops,
            'contextByGop' => $context->groupBy('gopID'),
            'dictRows' => $dictRows,
            'pdtFields'  => $schema->describe(self::PDT),
            'gopFields'  => $schema->describe(self::GOP),
            'ctxFields'  => $schema->describe(self::PROP),
            'dictFields' => $schema->describe(self::DICT),
            'dictEnums'  => \App\Services\BsddEnums::dictionaryFieldEnums(),
        ]);
    }

    /** Dry-run: build the plan and return the summary. Writes nothing. */
    public function preview(Request $request, int $pdt, VersioningService $service)
    {
        try {
            $plan = $service->planPdtVersion($pdt, $this->staged($request));
            return response()->json([
                'ok' => true,
                'summary' => $plan['summary'] ?? [],
                'opCount' => count($plan['ops'] ?? []),
                'bump' => $plan['bump'] ?? null,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** Commit: re-plan from the staged diff (fresh DB read) and apply in one transaction. */
    public function commit(Request $request, int $pdt, VersioningService $service)
    {
        try {
            $pdtRow = DB::table(self::PDT)->where('Id', $pdt)->first();
            $plan = $service->planPdtVersion($pdt, $this->staged($request));
            $result = $service->applyPlan($plan);

            // New head of this lineage (the version we just created) for redirect.
            $newHead = $pdtRow ? DB::table(self::PDT)->where('GUID', $pdtRow->GUID)
                ->where('status', VersioningService::ST_ACTIVE)
                ->orderByDesc('versionNumber')->orderByDesc('revisionNumber')->first() : null;

            return response()->json([
                'ok' => true,
                'result' => $result,
                'newPdtId' => $newHead->Id ?? $pdt,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /** Normalise the staged payload from the request into the planPdtVersion shape. */
    private function staged(Request $request): array
    {
        $s = (array) $request->input('staged', []);
        return [
            'pdt'           => ['attributes' => (array) ($s['pdt']['attributes'] ?? [])],
            'removeGopIds'  => array_map('intval', (array) ($s['removeGopIds'] ?? [])),
            'addGops'       => (array) ($s['addGops'] ?? []),
            'gopEdits'      => (array) ($s['gopEdits'] ?? []),
            'propertyEdits' => (array) ($s['propertyEdits'] ?? []),
            'contextEdits'  => (array) ($s['contextEdits'] ?? []),
        ];
    }
}
