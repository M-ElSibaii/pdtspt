<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Lightweight admin search endpoints powering the "add from existing" pickers in the
 * Preview editor (3b) and the staged versioning editor (3c). Read-only JSON.
 */
class AdminLookupController extends Controller
{
    /** Search dictionary properties by nameEn/namePt; returns latest-per-row matches. */
    public function properties(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $rows = DB::table('propertiesdatadictionaries')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('nameEn', 'like', "%{$q}%")
                      ->orWhere('namePt', 'like', "%{$q}%");
                });
            })
            ->where('status', 'Active')
            ->orderBy('nameEn')
            ->limit(25)
            ->get(['Id', 'GUID', 'nameEn', 'namePt', 'definitionEn', 'definitionPt', 'dataType', 'units', 'versionNumber', 'revisionNumber']);

        return response()->json(['results' => $rows]);
    }

    /** Search groups of properties by name; returns Active rows for cloning attrs. */
    public function gops(Request $request)
    {
        $q = trim((string) $request->query('q', ''));
        $rows = DB::table('groupofproperties')
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('gopNameEn', 'like', "%{$q}%")
                      ->orWhere('gopNamePt', 'like', "%{$q}%");
                });
            })
            ->where('status', 'Active')
            ->orderBy('gopNameEn')
            ->limit(25)
            ->get(['Id', 'GUID', 'gopNameEn', 'gopNamePt', 'definitionEn', 'definitionPt', 'versionNumber', 'revisionNumber']);

        return response()->json(['results' => $rows]);
    }
}
