<?php

namespace App\Console\Commands;

use App\Services\BsddEnums;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Reconcile propertiesdatadictionaries.dataType / units against the bSDD controlled
 * vocabularies (App\Services\BsddEnums). Dry-run by default: scans every row, flags any
 * non-blank value not in the enum, and classifies each flag as AUTO-CORRECTABLE (an exact
 * normalisation — whitespace, case, or CP1252↔UTF-8 mojibake — maps it to a valid value)
 * vs NEEDS-MANUAL-DECISION. Blank units are valid and never flagged.
 *
 * --apply: auto-correct only the unambiguous matches (JSON backup + single transaction,
 * rollback on error), leave manual rows untouched, and write a CSV of the manual rows.
 * Before writing it verifies the units/dataType columns are utf8mb4 so we never add new
 * mojibake on top of old.
 */
class ReconcileEnums extends Command
{
    protected $signature = 'pdts:reconcile-enums {--apply : Persist the unambiguous auto-corrections (otherwise dry run)} {--report : Export ALL flagged rows to CSV for manual review (no writes)}';
    protected $description = 'Reconcile dictionary dataType/units against the bSDD enums (dry-run first).';

    private const TABLE = 'propertiesdatadictionaries';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? '=== APPLY MODE ===' : '=== DRY RUN (no changes will be written) ===');

        $validDataType = BsddEnums::dataType();
        $validUnits = BsddEnums::units();

        $scanned = 0;
        $flagged = ['dataType' => [], 'units' => []]; // each: [id,nameEn,bad,suggestion,reason,auto]

        DB::table(self::TABLE)->select('Id', 'nameEn', 'dataType', 'units')->orderBy('Id')
            ->chunk(500, function ($rows) use (&$scanned, &$flagged, $validDataType, $validUnits) {
                foreach ($rows as $r) {
                    $scanned++;
                    foreach (['dataType' => $validDataType, 'units' => $validUnits] as $field => $valid) {
                        $val = $r->$field;
                        if ($val === null || trim((string) $val) === '') {
                            continue; // blank is valid (units especially) — never flag
                        }
                        if (in_array($val, $valid, true)) {
                            continue; // already valid
                        }
                        [$auto, $suggestion, $reason] = $this->evaluate((string) $val, $valid);
                        $flagged[$field][] = [
                            'id' => $r->Id, 'nameEn' => $r->nameEn, 'bad' => $val,
                            'suggestion' => $suggestion, 'reason' => $reason, 'auto' => $auto,
                        ];
                    }
                }
            });

        // ---- report ----
        foreach (['dataType', 'units'] as $field) {
            $list = $flagged[$field];
            $auto = array_filter($list, fn($f) => $f['auto']);
            $manual = array_filter($list, fn($f) => !$f['auto']);
            $this->line('');
            $this->info("── {$field}: " . count($list) . ' flagged (' . count($auto) . ' auto-correctable, ' . count($manual) . ' manual) ──');
            foreach (array_slice($list, 0, 60) as $f) {
                $tag = $f['auto'] ? "AUTO[{$f['reason']}] → '{$f['suggestion']}'" : 'MANUAL';
                $this->line(sprintf("   Id=%s  %s  bad='%s'  %s", $f['id'], $f['nameEn'], $f['bad'], $tag));
            }
            if (count($list) > 60) {
                $this->line('   … ' . (count($list) - 60) . ' more');
            }
        }

        $totalAuto = count(array_filter($flagged['dataType'], fn($f) => $f['auto'])) + count(array_filter($flagged['units'], fn($f) => $f['auto']));
        $totalManual = count(array_filter($flagged['dataType'], fn($f) => !$f['auto'])) + count(array_filter($flagged['units'], fn($f) => !$f['auto']));
        $this->line('');
        $this->info("SUMMARY: scanned {$scanned} rows · dataType flagged " . count($flagged['dataType'])
            . ' · units flagged ' . count($flagged['units']) . " · auto-correctable {$totalAuto} · manual {$totalManual}");

        // Read-only export of every flagged row for manual review (no DB writes).
        if ($this->option('report')) {
            $stamp = now()->format('Ymd_His');
            $path = storage_path("app/reconcile_enums_review_{$stamp}.csv");
            $fh = fopen($path, 'w');
            fputcsv($fh, ['field', 'Id', 'nameEn', 'badValue', 'classification', 'suggestion']);
            foreach (['dataType', 'units'] as $field) {
                foreach ($flagged[$field] as $f) {
                    fputcsv($fh, [$field, $f['id'], $f['nameEn'], $f['bad'], $f['auto'] ? "AUTO[{$f['reason']}]" : 'MANUAL', $f['suggestion'] ?? '']);
                }
            }
            fclose($fh);
            $this->info('Review report written: ' . basename($path) . ' (no changes made).');
        }

        if (!$apply) {
            $this->newLine();
            $this->info('Dry run complete. Re-run with --apply to auto-correct the unambiguous matches.');
            return self::SUCCESS;
        }

        // ---- apply ----
        if (!$this->columnsAreUtf8mb4()) {
            $this->error('Aborting: dataType/units columns are not utf8mb4 — fix the column charset first to avoid new mojibake.');
            return self::FAILURE;
        }

        $stamp = now()->format('Ymd_His');
        $affectedIds = collect(array_merge($flagged['dataType'], $flagged['units']))->where('auto', true)->pluck('id')->unique()->values();
        $backup = ['generated_at' => now()->toIso8601String(),
            'rows' => DB::table(self::TABLE)->whereIn('Id', $affectedIds->all() ?: [0])->get(['Id', 'nameEn', 'dataType', 'units'])];
        $backupPath = storage_path("app/reconcile_enums_backup_{$stamp}.json");
        file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $applied = 0;
        try {
            DB::transaction(function () use ($flagged, &$applied) {
                foreach (['dataType', 'units'] as $field) {
                    foreach ($flagged[$field] as $f) {
                        if ($f['auto']) {
                            DB::table(self::TABLE)->where('Id', $f['id'])->update([$field => $f['suggestion']]);
                            $applied++;
                        }
                    }
                }
            });
        } catch (\Throwable $e) {
            $this->error('Failed and rolled back: ' . $e->getMessage());
            return self::FAILURE;
        }

        // CSV of the manual rows for hand-resolution.
        $manualRows = collect(array_merge(
            array_map(fn($f) => $f + ['field' => 'dataType'], array_filter($flagged['dataType'], fn($f) => !$f['auto'])),
            array_map(fn($f) => $f + ['field' => 'units'], array_filter($flagged['units'], fn($f) => !$f['auto'])),
        ));
        $csvPath = storage_path("app/reconcile_enums_manual_{$stamp}.csv");
        $fh = fopen($csvPath, 'w');
        fputcsv($fh, ['Id', 'nameEn', 'field', 'badValue']);
        foreach ($manualRows as $f) {
            fputcsv($fh, [$f['id'], $f['nameEn'], $f['field'], $f['bad']]);
        }
        fclose($fh);

        $this->info("Applied {$applied} auto-correction(s). Manual rows: " . $manualRows->count() . ".");
        $this->info('Backup: ' . basename($backupPath) . ' · Manual report: ' . basename($csvPath));
        return self::SUCCESS;
    }

    /** [auto, suggestion, reason] — whether a normalisation maps $val to a valid enum value. */
    private function evaluate(string $val, array $valid): array
    {
        $trim = trim($val);
        if ($trim !== $val && in_array($trim, $valid, true)) {
            return [true, $trim, 'whitespace'];
        }
        foreach ($valid as $v) {
            if (strcasecmp($trim, $v) === 0) {
                return [true, $v, 'case'];
            }
        }
        // CP1252↔UTF-8 mojibake repair (e.g. "ÎÂ°C" → "°C").
        $rep = @mb_convert_encoding($val, 'Windows-1252', 'UTF-8');
        if ($rep !== false && mb_check_encoding($rep, 'UTF-8')) {
            $rt = trim($rep);
            if (in_array($rt, $valid, true)) {
                return [true, $rt, 'encoding'];
            }
            foreach ($valid as $v) {
                if (strcasecmp($rt, $v) === 0) {
                    return [true, $v, 'encoding+case'];
                }
            }
        }
        return [false, null, 'manual'];
    }

    private function columnsAreUtf8mb4(): bool
    {
        foreach (DB::select("SHOW FULL COLUMNS FROM " . self::TABLE . " WHERE Field IN ('dataType','units')") as $c) {
            if ($c->Collation !== null && !str_starts_with((string) $c->Collation, 'utf8mb4')) {
                return false;
            }
        }
        return true;
    }
}
