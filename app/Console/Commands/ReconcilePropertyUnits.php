<?php

namespace App\Console\Commands;

use App\Services\UnitsReference;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Stage 4 — reconcile every propertiesdatadictionaries row's physicalQuantity / dimension
 * against its existing unit, using the ISO 23387 reference tables as the source of truth.
 *
 *  - unit maps to a known row -> physicalQuantity := clean canonical name (no "| language",
 *    which is applied only on export); dimension linked when the tables carry one (physics is
 *    currently deferred, so dimension is left UNCHANGED for mapped units rather than fabricated).
 *  - unit blank (unit-less / text) -> physicalQuantity := "without", dimension cleared.
 *  - unit not found in the map -> FLAGGED for review (written to a downloadable file); never guessed.
 *
 * Dry-run by default (counts only). --apply writes inside a transaction with a JSON backup
 * and rolls back on any error. utf8mb4 is verified before any write.
 */
class ReconcilePropertyUnits extends Command
{
    protected $signature = 'properties:reconcile-units {--apply : Persist changes (default is a dry run)}';
    protected $description = 'Reconcile properties physicalQuantity/dimension from their unit via the ISO 23387 reference tables.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $storageDir = storage_path('app/units-reconcile');
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0775, true);
        }
        $stamp = date('Ymd_His');

        // --- utf8mb4 guard (constraint: confirm before writing special characters) ---
        $charset = DB::connection()->getConfig('charset');
        if ($apply && strtolower((string) $charset) !== 'utf8mb4') {
            $this->error("Connection charset is '{$charset}', not utf8mb4. Aborting --apply to avoid corrupting unit symbols.");
            return self::FAILURE;
        }

        $rows = DB::table('propertiesdatadictionaries')
            ->select('Id', 'GUID', 'nameEn', 'namePt', 'units', 'physicalQuantity', 'dimension')
            ->get();

        $planned = [];   // rows to update: [Id => ['physicalQuantity'=>..., 'dimension'=>...]]
        $flagged = [];   // rows with an unrecognised unit
        $backup  = [];   // pre-change snapshot for rows we will touch
        $counts = ['fixed' => 0, 'flagged' => 0, 'unchanged' => 0];

        foreach ($rows as $r) {
            $unit = trim((string) ($r->units ?? ''));
            $curPq = (string) ($r->physicalQuantity ?? '');
            $curDim = (string) ($r->dimension ?? '');

            if ($unit === '') {
                // Unit-less / text: physical quantity "without", no dimension.
                $targetPq = UnitsReference::WITHOUT;
                $targetDim = '';
            } elseif (UnitsReference::isKnownUnit($unit)) {
                $targetPq = UnitsReference::physicalQuantityFor($unit); // clean name
                // Physics deferred: keep existing dimension for mapped units (no fabrication).
                $targetDim = $curDim;
            } else {
                // Unknown unit — flag, change nothing.
                $flagged[] = [
                    'Id' => $r->Id,
                    'GUID' => $r->GUID,
                    'name' => $r->nameEn ?: $r->namePt,
                    'unit' => $unit,
                    'physicalQuantity' => $curPq,
                    'dimension' => $curDim,
                ];
                $counts['flagged']++;
                continue;
            }

            if ($targetPq === $curPq && $targetDim === $curDim) {
                $counts['unchanged']++;
                continue;
            }

            $planned[$r->Id] = ['physicalQuantity' => $targetPq, 'dimension' => $targetDim];
            $backup[] = [
                'Id' => $r->Id,
                'GUID' => $r->GUID,
                'from' => ['physicalQuantity' => $curPq, 'dimension' => $curDim],
                'to'   => ['physicalQuantity' => $targetPq, 'dimension' => $targetDim],
                'unit' => $unit,
            ];
            $counts['fixed']++;
        }

        // Always write the flag list (downloadable) so it can be reviewed.
        $flagPath = $storageDir . "/flagged_units_{$stamp}.json";
        file_put_contents($flagPath, json_encode($flagged, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $distinctFlagUnits = collect($flagged)->pluck('unit')->unique()->values()->all();

        $this->info(($apply ? 'APPLY' : 'DRY-RUN') . ' — properties unit reconciliation');
        $this->table(
            ['Fixed', 'Flagged', 'Unchanged', 'Total scanned'],
            [[$counts['fixed'], $counts['flagged'], $counts['unchanged'], $rows->count()]]
        );
        $this->line("Distinct unmapped units: " . count($distinctFlagUnits)
            . ($distinctFlagUnits ? ' -> ' . implode(', ', array_slice($distinctFlagUnits, 0, 30))
                . (count($distinctFlagUnits) > 30 ? ' …' : '') : ''));
        $this->line("Flagged list (downloadable): {$flagPath}");
        $this->line("Note: dimension left unchanged for mapped units (physics deferred); cleared only for unit-less rows.");

        if (!$apply) {
            $previewPath = $storageDir . "/dryrun_changes_{$stamp}.json";
            file_put_contents($previewPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            $this->line("Planned changes preview: {$previewPath}");
            $this->comment('Dry run only — nothing written. Re-run with --apply to persist.');
            return self::SUCCESS;
        }

        if (empty($planned)) {
            $this->comment('Nothing to apply.');
            return self::SUCCESS;
        }

        // JSON backup of every row we are about to change.
        $backupPath = $storageDir . "/backup_{$stamp}.json";
        file_put_contents($backupPath, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->line("Backup written: {$backupPath}");

        try {
            DB::transaction(function () use ($planned) {
                foreach ($planned as $id => $vals) {
                    DB::table('propertiesdatadictionaries')->where('Id', $id)->update($vals);
                }
            });
        } catch (\Throwable $e) {
            $this->error('Apply failed and was rolled back: ' . $e->getMessage());
            $this->line("Restore reference: {$backupPath}");
            return self::FAILURE;
        }

        $this->info("Applied {$counts['fixed']} updates (transaction committed).");
        return self::SUCCESS;
    }
}
