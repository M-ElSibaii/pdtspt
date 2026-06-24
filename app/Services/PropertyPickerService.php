<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

/**
 * Backing logic for the property picker (reused by CREATE and every add-property step),
 * reworked from the original PropertiesController::addFromDictionary / uploadExcel:
 *   - lists ONLY Active dictionary properties, latest row per GUID lineage;
 *   - carries definitions so rows are distinguishable (not just names);
 *   - matches uploaded names STRICTLY on nameEn, exact (case- and accent-sensitive, no
 *     fuzzy, no namePt fallback) — done in PHP with === to avoid the DB collation's
 *     case/accent-insensitive comparison the old whereIn() relied on;
 *   - produces the gap list (uploaded names with no Active dictionary match) for export.
 */
class PropertyPickerService
{
    private const DICT = 'propertiesdatadictionaries';

    /**
     * Active dictionary properties, one (latest) row per GUID lineage, with definitions.
     *
     * @return \Illuminate\Support\Collection<int,object>
     */
    public function activeProperties()
    {
        // Only Active rows; if a GUID somehow has more than one Active row (e.g. before a
        // recompute), keep the highest version/revision.
        $rows = DB::table(self::DICT)
            ->where('status', 'Active')
            ->orderByDesc('versionNumber')->orderByDesc('revisionNumber')
            ->get(['Id', 'GUID', 'nameEn', 'namePt', 'definitionEn', 'definitionPt', 'dataType', 'units', 'versionNumber', 'revisionNumber']);

        return $rows->groupBy('GUID')->map->first()->values()->sortBy('nameEn')->values();
    }

    /**
     * Exact-nameEn match of uploaded names against Active dictionary properties.
     *
     * @param  string[] $names  raw uploaded names
     * @return array{matchedIds:int[], matchedRows:array, unmatched:string[], matchedCount:int, unmatchedCount:int}
     */
    public function matchNames(array $names): array
    {
        // De-dup the upload while preserving order; trim only outer whitespace (exact otherwise).
        $clean = [];
        foreach ($names as $n) {
            $n = is_string($n) ? trim($n) : '';
            if ($n !== '' && !in_array($n, $clean, true)) {
                $clean[] = $n;
            }
        }

        // Compare against row values directly (not array keys) — PHP would coerce a
        // numeric-looking nameEn used as an array key to an int and break strict ===.
        // Compare on surrounding-whitespace-trimmed values: some stored nameEn have stray
        // leading/trailing spaces (a data defect), which must not defeat a legitimate
        // match. Internal case & accents stay strict (no fuzzy, no namePt fallback).
        $active = $this->activeProperties();
        $matchedRows = [];
        $unmatched = [];
        foreach ($clean as $name) {
            $hit = $active->first(fn($r) => trim((string) $r->nameEn) === $name);
            if ($hit) {
                $matchedRows[] = $hit;
            } else {
                $unmatched[] = $name;
            }
        }

        return [
            'matchedIds'     => array_map(fn($r) => (int) $r->Id, $matchedRows),
            'matchedRows'    => $matchedRows,
            'unmatched'      => $unmatched,
            'matchedCount'   => count($matchedRows),
            'unmatchedCount' => count($unmatched),
        ];
    }
}
