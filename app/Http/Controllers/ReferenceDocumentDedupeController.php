<?php

namespace App\Http\Controllers;

use App\Services\ReferenceDocumentDedupeService;
use Illuminate\Http\Request;

/**
 * Admin review tool for deduplicating `referencedocuments`, the counterpart of
 * {@see DictionaryDedupeController}. All logic lives in the service; one group is
 * resolved per request.
 */
class ReferenceDocumentDedupeController extends Controller
{
    public function index(ReferenceDocumentDedupeService $service)
    {
        $schemaError = $service->schemaError();

        return view('admin.dedupe-refdocs', [
            'groups'      => $schemaError ? [] : $service->analyzeGroups(),
            'schemaError' => $schemaError,
        ]);
    }

    /** Re-analyze one group, to refresh its card after applying. */
    public function group(Request $request, ReferenceDocumentDedupeService $service)
    {
        $key = (string) $request->query('key', '');

        return response()->json(['group' => $key === '' ? null : $service->analyzeGroup($key)]);
    }

    /** Apply one group's decision. Re-validation, backup and transaction are in the service. */
    public function apply(Request $request, ReferenceDocumentDedupeService $service)
    {
        $decision = (array) $request->input('decision', []);

        try {
            $result = $service->applyDecision($decision);

            return response()->json([
                'ok'     => true,
                'result' => $result,
                'group'  => $service->analyzeGroup((string) ($decision['key'] ?? '')),
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }
}
