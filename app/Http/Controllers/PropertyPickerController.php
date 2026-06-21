<?php

namespace App\Http\Controllers;

use App\Exports\GapPropertiesExport;
use App\Services\PropertyPickerService;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Shared property-picker endpoints (CREATE + every add-property step). Reworked from the
 * original addFromDictionary/uploadExcel: Active-only, descriptions included, strict exact
 * nameEn matching, and a downloadable gap list for the names that need creation.
 */
class PropertyPickerController extends Controller
{
    /** Active dictionary properties (latest per GUID) with definitions, optional ?q= filter. */
    public function properties(Request $request, PropertyPickerService $picker)
    {
        $q = trim((string) $request->query('q', ''));
        $rows = $picker->activeProperties();
        if ($q !== '') {
            $needle = mb_strtolower($q);
            $rows = $rows->filter(fn($r) => str_contains(mb_strtolower($r->nameEn), $needle)
                || str_contains(mb_strtolower((string) $r->namePt), $needle))->values();
        }
        return response()->json(['results' => $rows->take(200)->values()]);
    }

    /**
     * Upload an Excel/CSV of wanted property names -> exact (case/accent-sensitive) match
     * on nameEn against Active dictionary properties. Returns matched dict Ids (to auto-
     * select) and the unmatched names (the gap list, downloadable via exportGap).
     *
     * Scoped to ONE sheet: the sheet whose name equals the current GOP name (normalised to
     * alpha-only, case-insensitive — the original uploadExcel behaviour). Only that sheet's
     * names are read; other sheets are ignored, so each group's upload maps to its own list.
     */
    public function matchExcel(Request $request, PropertyPickerService $picker)
    {
        $request->validate([
            'excelFile' => 'required|mimes:xlsx,xls,csv|max:4096',
            'groupName' => 'nullable|string',
        ]);

        $file = $request->file('excelFile');
        try {
            $excelData = Excel::toArray([], $file);
            // Sheet names (Excel::toArray loses them) via the underlying reader.
            $reader = \PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile($file->getRealPath());
            $sheetNames = $reader->load($file->getRealPath())->getSheetNames();
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'error' => 'Could not read the file: ' . $e->getMessage()], 422);
        }

        $norm = fn($s) => strtolower(preg_replace('/[^a-zA-Z]/', '', (string) $s));
        $wanted = $norm($request->input('groupName', ''));

        $names = [];
        $sheetMatched = false;
        foreach ($sheetNames as $idx => $sheetName) {
            if ($wanted !== '' && $norm($sheetName) === $wanted) {
                $sheetMatched = true;
                foreach (($excelData[$idx] ?? []) as $row) {
                    foreach ((array) $row as $cell) {
                        $v = trim((string) $cell);
                        if ($v !== '') {
                            $names[] = $v;
                        }
                    }
                }
                break; // only the matching sheet
            }
        }

        $result = $picker->matchNames($names);

        return response()->json([
            'ok'             => true,
            'sheetMatched'   => $sheetMatched,
            'sheetNames'     => $sheetNames,
            'matchedIds'     => $result['matchedIds'],
            'matchedNames'   => array_map(fn($r) => $r->nameEn, $result['matchedRows']),
            'unmatched'      => $result['unmatched'],
            'matchedCount'   => $result['matchedCount'],
            'unmatchedCount' => $result['unmatchedCount'],
        ]);
    }

    /** Download the gap list (names needing creation) as an .xlsx. */
    public function exportGap(Request $request)
    {
        $names = array_values(array_filter(array_map('trim', (array) $request->input('names', [])), fn($n) => $n !== ''));
        $filename = 'properties_to_create_' . now()->format('Ymd_His') . '.xlsx';
        return Excel::download(new GapPropertiesExport($names), $filename);
    }
}
