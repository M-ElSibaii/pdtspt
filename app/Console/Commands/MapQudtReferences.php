<?php

namespace App\Console\Commands;

use App\Models\Unit;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fix #2 — populate units.reference_uri (and physical_quantities.reference_uri where a QUDT
 * quantitykind is available) from the bSDD units spreadsheet resources/data/units.csv
 * (columns: Code,Name,Symbol,QUDT). QUDT tokens resolve to http://qudt.org/vocab/unit/{token}.
 *
 * The CSV Code column is double-encoded UTF-8 (e.g. "Â°C" for "°C"), so we match tolerantly:
 * raw code, de-double-encoded code, then by name. Units without a QUDT token are left null and
 * reported. Idempotent: only rows whose reference_uri actually changes are written. utf8mb4 is
 * verified before writing.
 */
class MapQudtReferences extends Command
{
    protected $signature = 'units:map-qudt {--apply : Persist changes (default is a dry run)}';
    protected $description = 'Map QUDT reference URIs onto units (and quantities) from resources/data/units.csv.';

    private const QUDT_UNIT_BASE = 'http://qudt.org/vocab/unit/';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $csvPath = resource_path('data/units.csv');
        if (!is_file($csvPath)) {
            $this->error("Missing source sheet: {$csvPath}");
            return self::FAILURE;
        }

        $charset = DB::connection()->getConfig('charset');
        if ($apply && strtolower((string) $charset) !== 'utf8mb4') {
            $this->error("Connection charset is '{$charset}', not utf8mb4. Aborting --apply.");
            return self::FAILURE;
        }

        // Index units by both correct code and (defensively) de-double-encoded code + name.
        $units = Unit::all();
        $byCode = [];
        $byName = [];
        foreach ($units as $u) {
            $byCode[$u->code] = $u;
            $byName[mb_strtolower($u->name ?? '')] = $u;
        }

        $rows = array_map('str_getcsv', file($csvPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES));
        array_shift($rows); // header

        $counts = ['matched' => 0, 'withQudt' => 0, 'changed' => 0, 'noQudt' => 0, 'unmatchedUnit' => 0];
        $planned = [];   // [unitGuid => uri]
        $backup = [];
        $unmatched = [];   // csv rows with no unit
        $noQudt = [];      // matched unit but no QUDT token

        foreach ($rows as $r) {
            $rawCode = $r[0] ?? '';
            $name = $r[1] ?? '';
            $qudt = trim((string) ($r[3] ?? ''));

            $fixedCode = self::deDoubleEncode($rawCode);
            $unit = $byCode[$rawCode] ?? $byCode[$fixedCode] ?? $byName[mb_strtolower($name)] ?? null;

            if (!$unit) {
                $counts['unmatchedUnit']++;
                $unmatched[] = ['code' => $rawCode, 'name' => $name, 'qudt' => $qudt];
                continue;
            }
            $counts['matched']++;

            if ($qudt === '' || strtoupper($qudt) === 'NULL') {
                $counts['noQudt']++;
                $noQudt[] = ['code' => $unit->code, 'name' => $unit->name];
                continue;
            }
            $counts['withQudt']++;
            $uri = self::QUDT_UNIT_BASE . $qudt;

            if ((string) $unit->reference_uri !== $uri) {
                $planned[$unit->guid] = $uri;
                $backup[] = ['guid' => $unit->guid, 'code' => $unit->code, 'from' => $unit->reference_uri, 'to' => $uri];
                $counts['changed']++;
            }
        }

        $dir = storage_path('app/units-reconcile');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $stamp = date('Ymd_His');
        file_put_contents("$dir/qudt_unmatched_units_{$stamp}.json", json_encode($unmatched, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        file_put_contents("$dir/qudt_no_token_{$stamp}.json", json_encode($noQudt, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info(($apply ? 'APPLY' : 'DRY-RUN') . ' — QUDT reference mapping');
        $this->table(
            ['CSV rows', 'Unit matched', 'With QUDT', 'Would change', 'No QUDT token', 'Unmatched'],
            [[count($rows), $counts['matched'], $counts['withQudt'], $counts['changed'], $counts['noQudt'], $counts['unmatchedUnit']]]
        );
        $matchRate = count($rows) ? round(100 * $counts['matched'] / count($rows), 1) : 0;
        $qudtRate = $counts['matched'] ? round(100 * $counts['withQudt'] / $counts['matched'], 1) : 0;
        $this->line("Unit match rate: {$matchRate}%  |  QUDT coverage of matched units: {$qudtRate}%");
        $this->line("Physical-quantity QUDT URIs: sheet provides none -> physical_quantities.reference_uri left null (column present).");
        $this->line("Unmatched units list: $dir/qudt_unmatched_units_{$stamp}.json");
        $this->line("Units with no QUDT token: $dir/qudt_no_token_{$stamp}.json");

        if (!$apply) {
            $this->comment('Dry run only — nothing written. Re-run with --apply to persist.');
            return self::SUCCESS;
        }
        if (empty($planned)) {
            $this->comment('Nothing to change (already mapped).');
            return self::SUCCESS;
        }

        file_put_contents("$dir/qudt_backup_{$stamp}.json", json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        try {
            DB::transaction(function () use ($planned) {
                foreach ($planned as $guid => $uri) {
                    DB::table('units')->where('guid', $guid)->update(['reference_uri' => $uri]);
                }
            });
        } catch (\Throwable $e) {
            $this->error('Apply failed and was rolled back: ' . $e->getMessage());
            return self::FAILURE;
        }
        $this->info("Applied {$counts['changed']} unit reference_uri updates. Backup: $dir/qudt_backup_{$stamp}.json");
        return self::SUCCESS;
    }

    /** Collapse one layer of double-encoded UTF-8 (mojibake "Â°" -> "°"). */
    private static function deDoubleEncode(string $s): string
    {
        $decoded = @mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
        // Only accept the conversion if it yields valid UTF-8 (otherwise keep original).
        return ($decoded !== false && mb_check_encoding($decoded, 'UTF-8')) ? $decoded : $s;
    }
}
