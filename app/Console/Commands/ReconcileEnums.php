<?php

namespace App\Console\Commands;

use App\Services\BsddEnums;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reconcile propertiesdatadictionaries against the bSDD vocabularies:
 *   - units  -> normalise every non-blank value to a valid code from BsddEnums::units().
 *               Auto-correct only UNAMBIGUOUS transforms (whitespace, CP1252↔UTF-8 mojibake,
 *               ASCII-digit→superscript like m2→m², and case ONLY when exactly one
 *               case-insensitive match exists — so t/T, mV/MV etc. are never mis-folded).
 *   - PhysicalQuantity -> for any row whose final unit is valid, set "{name} | en.EN" from
 *               the code→name map; for a blank unit set "without". (Manual/unknown units are
 *               left untouched and reported.)
 *   - dataType -> flag any non-blank value not in the 6 valid values.
 * Blank units are valid and never flagged. Dry-run by default; --apply writes (JSON backup +
 * transaction, utf8mb4 guard). --report exports all flags/changes to CSV (no writes).
 */
class ReconcileEnums extends Command
{
    protected $signature = 'pdts:reconcile-enums {--apply : Persist auto-corrections + PhysicalQuantity} {--report : Export the full plan to CSV (no writes)}';
    protected $description = 'Reconcile dictionary units/dataType + PhysicalQuantity against the bSDD enums (dry-run first).';

    private const TABLE = 'propertiesdatadictionaries';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? '=== APPLY MODE ===' : '=== DRY RUN (no changes will be written) ===');

        $validUnits = BsddEnums::units();
        $unitMap = BsddEnums::unitsMap();
        $validDataType = BsddEnums::dataType();

        $scanned = 0;
        $unitStats = ['valid' => 0, 'auto' => 0, 'manual' => 0, 'blank' => 0];
        $unitAuto = [];     // [id,nameEn,from,to,reason]
        $unitManual = [];   // [id,nameEn,bad,reason]
        $dataTypeFlags = []; // [id,nameEn,bad]
        $pqChanges = [];    // [id,from,to]  (PhysicalQuantity to set)
        $plan = [];         // per-id: ['unit'=>?, 'pq'=>?]

        DB::table(self::TABLE)->select('Id', 'nameEn', 'units', 'physicalQuantity', 'dataType')->orderBy('Id')
            ->chunk(500, function ($rows) use (&$scanned, &$unitStats, &$unitAuto, &$unitManual, &$dataTypeFlags, &$pqChanges, &$plan, $validUnits, $unitMap, $validDataType) {
                foreach ($rows as $r) {
                    $scanned++;
                    [$status, $finalUnit, $reason] = $this->unitDecision($r->units, $validUnits);
                    $unitStats[$status]++;

                    $rowPlan = [];
                    if ($status === 'auto') {
                        $unitAuto[] = ['id' => $r->Id, 'nameEn' => $r->nameEn, 'from' => $r->units, 'to' => $finalUnit, 'reason' => $reason];
                        $rowPlan['unit'] = $finalUnit;
                    } elseif ($status === 'manual') {
                        $unitManual[] = ['id' => $r->Id, 'nameEn' => $r->nameEn, 'bad' => $r->units, 'reason' => $reason];
                    }

                    // PhysicalQuantity target: valid/auto -> "{name} | en.EN"; blank -> "without"; manual -> leave.
                    $pqTarget = null;
                    if ($status === 'blank') {
                        $pqTarget = 'without';
                    } elseif ($status === 'valid' || $status === 'auto') {
                        $name = $unitMap[$finalUnit] ?? null;
                        if ($name !== null) {
                            $pqTarget = $name . ' | en.EN';
                        }
                    }
                    if ($pqTarget !== null && (string) $r->physicalQuantity !== $pqTarget) {
                        $pqChanges[] = ['id' => $r->Id, 'from' => $r->physicalQuantity, 'to' => $pqTarget];
                        $rowPlan['pq'] = $pqTarget;
                    }

                    // dataType (report only)
                    if ($r->dataType !== null && trim((string) $r->dataType) !== '' && !in_array($r->dataType, $validDataType, true)) {
                        $dataTypeFlags[] = ['id' => $r->Id, 'nameEn' => $r->nameEn, 'bad' => $r->dataType];
                    }

                    if ($rowPlan) {
                        $plan[$r->Id] = $rowPlan;
                    }
                }
            });

        // ---- report ----
        $this->line('');
        $this->info("UNITS: scanned {$scanned} · valid {$unitStats['valid']} · auto-correctable {$unitStats['auto']} · manual {$unitStats['manual']} · blank {$unitStats['blank']}");
        $this->line('-- unit auto-corrections (sample) --');
        foreach (array_slice($unitAuto, 0, 40) as $f) {
            $this->line(sprintf("   Id=%s  '%s' -> '%s'  [%s]", $f['id'], $f['from'], $f['to'], $f['reason']));
        }
        if (count($unitAuto) > 40) {
            $this->line('   … ' . (count($unitAuto) - 40) . ' more');
        }
        $this->line('-- unit MANUAL (not auto-correctable; sample) --');
        foreach (array_slice($unitManual, 0, 40) as $f) {
            $this->line(sprintf("   Id=%s  %s  bad='%s'  [%s]", $f['id'], $f['nameEn'], $f['bad'], $f['reason']));
        }
        if (count($unitManual) > 40) {
            $this->line('   … ' . (count($unitManual) - 40) . ' more');
        }
        $this->line('');
        $this->info('PhysicalQuantity to set/normalise: ' . count($pqChanges) . ' row(s)');
        $this->info('dataType flagged (report only): ' . count($dataTypeFlags));

        if ($this->option('report')) {
            $this->writeReport($unitAuto, $unitManual, $pqChanges, $dataTypeFlags);
        }

        if (!$apply) {
            $this->newLine();
            $this->info('Dry run complete. Re-run with --apply to write the auto-corrections + PhysicalQuantity.');
            return self::SUCCESS;
        }

        // ---- apply ----
        if (!$this->columnsAreUtf8mb4()) {
            $this->error('Aborting: units/physicalQuantity columns are not utf8mb4 — fix charset first.');
            return self::FAILURE;
        }
        $stamp = now()->format('Ymd_His');
        $ids = array_keys($plan);
        $backup = ['generated_at' => now()->toIso8601String(),
            'rows' => DB::table(self::TABLE)->whereIn('Id', $ids ?: [0])->get(['Id', 'nameEn', 'units', 'physicalQuantity'])];
        $backupPath = storage_path("app/reconcile_enums_backup_{$stamp}.json");
        file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $unitWrites = 0;
        $pqWrites = 0;
        try {
            DB::transaction(function () use ($plan, &$unitWrites, &$pqWrites) {
                foreach ($plan as $id => $p) {
                    $upd = [];
                    if (array_key_exists('unit', $p)) { $upd['units'] = $p['unit']; $unitWrites++; }
                    if (array_key_exists('pq', $p)) { $upd['physicalQuantity'] = $p['pq']; $pqWrites++; }
                    if ($upd) {
                        DB::table(self::TABLE)->where('Id', $id)->update($upd);
                    }
                }
            });
        } catch (\Throwable $e) {
            $this->error('Failed and rolled back: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->writeReport($unitAuto, $unitManual, $pqChanges, $dataTypeFlags, $stamp);
        $this->info("Applied: {$unitWrites} unit correction(s), {$pqWrites} PhysicalQuantity write(s). Manual units left: " . count($unitManual) . '.');
        $this->info('Backup: ' . basename($backupPath));
        return self::SUCCESS;
    }

    /** [status, finalUnit, reason]; status = valid|auto|manual|blank. Safe transforms only. */
    private function unitDecision(?string $val, array $valid): array
    {
        $raw = (string) $val;
        if (trim($raw) === '') {
            return ['blank', '', 'blank'];
        }
        if (in_array($raw, $valid, true)) {
            return ['valid', $raw, 'exact'];
        }
        $bases = array_values(array_unique([trim($raw), trim($this->repair($raw))]));
        $sup = fn($s) => strtr($s, ['2' => '²', '3' => '³', '4' => '⁴']);

        // Unambiguous non-case transforms first.
        foreach ($bases as $b) {
            if (in_array($b, $valid, true)) {
                return ['auto', $b, $b === trim($raw) ? 'whitespace' : 'encoding'];
            }
            $sc = $sup($b);
            if ($sc !== $b && in_array($sc, $valid, true)) {
                return ['auto', $sc, 'superscript'];
            }
        }
        // Case match ONLY if exactly one valid code matches case-insensitively (incl. superscript).
        $ci = [];
        foreach ($valid as $v) {
            foreach ($bases as $b) {
                if (strcasecmp($b, $v) === 0 || strcasecmp($sup($b), $v) === 0) {
                    $ci[$v] = true;
                }
            }
        }
        $ci = array_keys($ci);
        if (count($ci) === 1) {
            return ['auto', $ci[0], 'case'];
        }
        return ['manual', $raw, count($ci) > 1 ? 'ambiguous-case' : 'not-found'];
    }

    private function repair(string $s): string
    {
        $r = @mb_convert_encoding($s, 'Windows-1252', 'UTF-8');
        return ($r !== false && $r !== '' && mb_check_encoding($r, 'UTF-8')) ? $r : $s;
    }

    private function writeReport(array $unitAuto, array $unitManual, array $pqChanges, array $dataTypeFlags, ?string $stamp = null): void
    {
        $stamp = $stamp ?: now()->format('Ymd_His');
        $path = storage_path("app/reconcile_enums_report_{$stamp}.csv");
        $fh = fopen($path, 'w');
        fputcsv($fh, ['section', 'Id', 'nameEn', 'from', 'to', 'reason']);
        foreach ($unitAuto as $f) {
            fputcsv($fh, ['unit-auto', $f['id'], $f['nameEn'], $f['from'], $f['to'], $f['reason']]);
        }
        foreach ($unitManual as $f) {
            fputcsv($fh, ['unit-manual', $f['id'], $f['nameEn'], $f['bad'], '', $f['reason']]);
        }
        foreach ($pqChanges as $f) {
            fputcsv($fh, ['physicalQuantity', $f['id'], '', $f['from'], $f['to'], '']);
        }
        foreach ($dataTypeFlags as $f) {
            fputcsv($fh, ['dataType-flag', $f['id'], $f['nameEn'], $f['bad'], '', '']);
        }
        fclose($fh);
        $this->info('Report written: ' . basename($path));
    }

    private function columnsAreUtf8mb4(): bool
    {
        foreach (DB::select("SHOW FULL COLUMNS FROM " . self::TABLE . " WHERE Field IN ('units','physicalQuantity','dataType')") as $c) {
            if ($c->Collation !== null && !str_starts_with((string) $c->Collation, 'utf8mb4')) {
                return false;
            }
        }
        return true;
    }
}
