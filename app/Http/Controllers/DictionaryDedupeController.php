<?php

namespace App\Http\Controllers;

use App\Services\DictionaryDedupeService;
use Illuminate\Http\Request;

/**
 * Admin-side interactive review tool for deduplicating propertiesdatadictionaries.
 * All logic is delegated to {@see DictionaryDedupeService} (shared with the
 * pdts:dedupe-dictionary command). One group is resolved per AJAX request.
 */
class DictionaryDedupeController extends Controller
{
    public function index(DictionaryDedupeService $service)
    {
        $schemaError = $service->schemaError();
        $groups = $schemaError ? [] : $service->analyzeGroups();

        return view('admin.dedupe-dictionary', [
            'groups'      => $groups,
            'schemaError' => $schemaError,
        ]);
    }

    /**
     * Re-analyze a single group (used to refresh a card after applying).
     */
    public function group(Request $request, DictionaryDedupeService $service)
    {
        $name = (string) $request->query('name', '');
        return response()->json(['group' => $name === '' ? null : $service->analyzeGroup($name)]);
    }

    /**
     * Edit a single referencing properties row's per-PDT descriptions.
     */
    public function updateProperty(Request $request, DictionaryDedupeService $service)
    {
        $data = $request->validate([
            'propertyId'    => 'required|integer',
            'descriptionEn' => 'nullable|string',
            'descriptionPt' => 'nullable|string',
        ]);

        try {
            $result = $service->updatePropertyDescription(
                (int) $data['propertyId'],
                $data['descriptionEn'] ?? null,
                $data['descriptionPt'] ?? null
            );

            return response()->json(['ok' => true, 'result' => $result]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => $e->getMessage()], 422);
        }
    }

    /**
     * Apply one group's decision. Re-validation, backup and transaction all happen
     * inside the service. Returns the result and the refreshed group analysis.
     */
    public function apply(Request $request, DictionaryDedupeService $service)
    {
        $decision = (array) $request->input('decision', []);

        try {
            $result = $service->applyDecision($decision);
            $group  = $service->analyzeGroup((string) ($decision['name'] ?? ''));

            return response()->json([
                'ok'     => true,
                'result' => $result,
                'group'  => $group, // null once fully resolved (no longer a dup group)
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'    => false,
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
