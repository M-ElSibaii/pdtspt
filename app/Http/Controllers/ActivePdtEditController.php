<?php

namespace App\Http\Controllers;

use App\Services\SchemaAttributeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Mode 2 — EDIT EXISTING ACTIVE PDT (limited, in-place). On an Active PDT the admin may
 * change ONLY a small allow-list of non-version attributes; everything else is read-only.
 * These are the sanctioned non-version edits (no GUID/version bump, no cascade) — distinct
 * from "Create new version" (Mode 4). The allow-list mirrors the existing editors:
 *   - context/properties row: descriptionEn, descriptionPt, visualRepresentation, referenceDocumentGUID
 *   - Active dictionary property: ONLY relationToOtherDataDictionaries (the mapping attribute)
 */
class ActivePdtEditController extends Controller
{
    private const DICT = 'propertiesdatadictionaries';
    private const GOP  = 'groupofproperties';
    private const PROP = 'properties';
    private const PDT  = 'productdatatemplates';

    public const CTX_EDITABLE  = ['descriptionEn', 'descriptionPt', 'visualRepresentation', 'referenceDocumentGUID'];
    public const DICT_EDITABLE = ['relationToOtherDataDictionaries'];

    private function assertActive(int $pdtId): object
    {
        $pdt = DB::table(self::PDT)->where('Id', $pdtId)->first();
        if (!$pdt) {
            abort(404);
        }
        if ($pdt->status !== 'Active') {
            throw new \RuntimeException("PDT Id {$pdtId} is '{$pdt->status}', not Active.");
        }
        return $pdt;
    }

    public function editor(int $pdt, SchemaAttributeService $schema)
    {
        try {
            $pdtRow = $this->assertActive($pdt);
        } catch (\Throwable $e) {
            return redirect()->route('dashboard')->with('error', $e->getMessage());
        }

        $gops = DB::table(self::GOP)->where('pdtId', $pdt)->orderBy('Id')->get();
        $context = DB::table(self::PROP . ' as p')
            ->leftJoin(self::DICT . ' as d', 'd.Id', '=', 'p.propertyId')
            ->where('p.pdtID', $pdt)
            ->select('p.Id', 'p.gopID', 'p.propertyId', 'p.descriptionEn', 'p.descriptionPt',
                'p.referenceDocumentGUID', 'p.visualRepresentation',
                'd.nameEn as dictNameEn', 'd.namePt as dictNamePt', 'd.dataType as dictDataType',
                'd.units as dictUnits', 'd.versionNumber as dictVersion', 'd.revisionNumber as dictRevision')
            ->orderBy('p.Id')->get();

        $dictIds = $context->pluck('propertyId')->filter()->unique()->values();
        $dictRows = $dictIds->isEmpty() ? collect() : DB::table(self::DICT)->whereIn('Id', $dictIds)->get()->keyBy('Id');

        return view('admin.active-edit', [
            'pdt' => $pdtRow,
            'gops' => $gops,
            'contextByGop' => $context->groupBy('gopID'),
            'dictRows' => $dictRows,
            'pdtFields'  => $schema->describe(self::PDT),
            'gopFields'  => $schema->describe(self::GOP),
            'ctxFields'  => $schema->describe(self::PROP),
            'dictFields' => $schema->describe(self::DICT),
            'ctxEditable'  => self::CTX_EDITABLE,
            'dictEditable' => self::DICT_EDITABLE,
        ]);
    }

    /** In-place edit of an allowed set of context (properties) fields on an Active PDT. */
    public function updateContext(Request $request, int $pdt)
    {
        return $this->guarded(function () use ($request, $pdt) {
            $this->assertActive($pdt);
            $contextId = (int) $request->input('contextId');
            $ctx = DB::table(self::PROP)->where('Id', $contextId)->where('pdtID', $pdt)->first();
            if (!$ctx) {
                throw new \RuntimeException('Context row not found on this PDT.');
            }
            $set = array_intersect_key((array) $request->input('attrs', []), array_flip(self::CTX_EDITABLE));
            if ($set) {
                DB::table(self::PROP)->where('Id', $contextId)->update($set);
            }
            return ['saved' => array_keys($set)];
        });
    }

    /** In-place edit of ONLY the mapping attribute on the referenced Active dictionary row. */
    public function updateDictMapping(Request $request, int $pdt)
    {
        return $this->guarded(function () use ($request, $pdt) {
            $this->assertActive($pdt);
            $contextId = (int) $request->input('contextId');
            $ctx = DB::table(self::PROP)->where('Id', $contextId)->where('pdtID', $pdt)->first();
            if (!$ctx || !$ctx->propertyId) {
                throw new \RuntimeException('Dictionary property not found for this context.');
            }
            $set = array_intersect_key((array) $request->input('attrs', []), array_flip(self::DICT_EDITABLE));
            if ($set) {
                DB::table(self::DICT)->where('Id', $ctx->propertyId)->update($set);
            }
            return ['saved' => array_keys($set), 'dictId' => (int) $ctx->propertyId];
        });
    }

    private function guarded(\Closure $fn)
    {
        try {
            $payload = $fn();
            return response()->json(['ok' => true] + (is_array($payload) ? $payload : []));
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
