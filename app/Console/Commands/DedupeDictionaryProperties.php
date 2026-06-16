<?php

namespace App\Console\Commands;

use App\Services\DictionaryDedupeService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Deduplicate properties in `propertiesdatadictionaries`.
 *
 * Grouping key: exact `nameEn`.
 * Survivor: lowest Id within the group (the "first instance").
 *
 * Merges only TRUE name duplicates — whether they share the survivor's GUID or
 * have a different GUID. VERSION VARIANTS (same GUID, different
 * versionNumber/revisionNumber) are legitimate version history and are left
 * completely untouched.
 *
 * All analysis, version-variant/conflict detection, the backup format and the
 * repoint-then-delete mutation live in {@see DictionaryDedupeService}, which is
 * shared with the admin review UI. This command is the batch front-end.
 *
 * For each duplicate group:
 *   1. Repoint `properties.propertyId` (-> dict.Id) and `properties.GUID`
 *      (-> dict.GUID) from each duplicate to the survivor's Id and GUID.
 *   2. Delete the duplicate rows from `propertiesdatadictionaries`.
 *
 * SAFETY:
 *   - Dry-run by default. Nothing is written unless --apply is passed.
 *   - --apply requires interactive confirmation.
 *   - Description conflicts (survivor vs duplicate differ on definitionEn/definitionPt)
 *     are REPORTED and, unless --force-descriptions is set, those groups are SKIPPED
 *     entirely so you can resolve them by hand first.
 *   - Everything runs inside a transaction; any error rolls back.
 *   - Writes a backup of affected rows to storage/ before mutating.
 *
 * NOTE: version variants are detected and reported under "LEFT UNTOUCHED: VERSION
 * VARIANTS" and are never repointed or deleted.
 *
 * Usage:
 *   php artisan pdts:dedupe-dictionary                 # dry run report
 *   php artisan pdts:dedupe-dictionary --apply         # apply (skips conflicts)
 *   php artisan pdts:dedupe-dictionary --apply --force-descriptions
 */
class DedupeDictionaryProperties extends Command
{
    protected $signature = 'pdts:dedupe-dictionary
        {--apply : Actually perform the updates and deletes (otherwise dry run)}
        {--force-descriptions : Merge groups even when definitions differ (keeps survivor definition)}';

    protected $description = 'Deduplicate propertiesdatadictionaries by nameEn, repointing properties table references first.';

    public function handle(DictionaryDedupeService $service): int
    {
        $apply             = (bool) $this->option('apply');
        $forceDescriptions = (bool) $this->option('force-descriptions');

        $this->info($apply ? '=== APPLY MODE ===' : '=== DRY RUN (no changes will be written) ===');

        if ($err = $service->schemaError()) {
            $this->error($err . ' Edit the constants in ' . DictionaryDedupeService::class . ' to match your schema.');
            return self::FAILURE;
        }

        $groups = $service->analyzeGroups();

        if (empty($groups)) {
            $this->info('No duplicates found. Nothing to do.');
            return self::SUCCESS;
        }

        $this->info('Found ' . count($groups) . ' duplicated nameEn value(s).');
        $this->newLine();

        // Sort analyzed groups into the report buckets (preserving original behaviour).
        $plan          = [];   // mergeable groups (raw survivor + duplicates for apply)
        $conflicts     = [];   // skipped due to description conflict
        $versioned     = [];   // groups with version variants left untouched
        $totalRepoints = 0;
        $totalDeletes  = 0;

        foreach ($groups as $g) {
            if (!empty($g['versionVariants'])) {
                $versioned[] = $g;
            }

            if (!$g['isActionable']) {
                continue; // only version variants here — nothing to merge
            }

            if ($g['hasDescriptionConflict'] && !$forceDescriptions) {
                $conflicts[] = $g;
                continue;
            }

            $plan[] = $g;
            $totalRepoints += $g['affectedCount'];
            $totalDeletes  += $g['_duplicateRows']->count();
        }

        // ---------- REPORT ----------
        $this->line('────────── PLANNED MERGES ──────────');
        foreach ($plan as $g) {
            $this->line(sprintf(
                "• %s  | survivor %s  | merging %d duplicate(s)  | repointing %d properties row(s)",
                $g['name'],
                $service->rowLabel($g['_survivorRow']),
                $g['_duplicateRows']->count(),
                $g['affectedCount']
            ));
            foreach ($g['_duplicateRows'] as $d) {
                $this->line(sprintf("      └─ drop %s", $service->rowLabel($d)));
            }
        }

        if (!empty($conflicts)) {
            $this->newLine();
            $this->warn('────────── SKIPPED: DESCRIPTION CONFLICTS (resolve manually) ──────────');
            foreach ($conflicts as $c) {
                $this->warn("• {$c['name']}  | survivor {$service->rowLabel($c['_survivorRow'])}");
                foreach (DictionaryDedupeService::DESC_COLS as $col) {
                    $this->line("      survivor {$col}: " . $this->snippet($c['survivor'][$col] ?? null));
                }
                foreach ($c['duplicates'] as $cc) {
                    $this->line(sprintf("      conflict %s", $service->rowLabel($cc)));
                    foreach (DictionaryDedupeService::DESC_COLS as $col) {
                        $this->line(sprintf("            %s: %s", $col, $this->snippet($cc[$col] ?? null)));
                    }
                }
            }
            $this->newLine();
            $this->warn("Re-run with --force-descriptions to merge these anyway (survivor's definition is kept).");
        }

        $versionRowCount = 0;
        if (!empty($versioned)) {
            $this->newLine();
            $this->line('────────── LEFT UNTOUCHED: VERSION VARIANTS (same GUID, different version) ──────────');
            foreach ($versioned as $v) {
                $this->line("• {$v['name']}  | keeping {$service->rowLabel($v['_survivorRow'])}");
                foreach ($v['versionVariants'] as $r) {
                    $versionRowCount++;
                    $this->line(sprintf("      ~ untouched %s", $service->rowLabel($r)));
                }
            }
        }

        $this->newLine();
        $this->info(sprintf(
            'SUMMARY: %d group(s) to merge, %d properties row(s) to repoint, %d dictionary row(s) to delete. %d group(s) skipped for conflicts. %d version variant(s) left untouched.',
            count($plan),
            $totalRepoints,
            $totalDeletes,
            count($conflicts),
            $versionRowCount
        ));

        if (!$apply) {
            $this->newLine();
            $this->info('Dry run complete. Re-run with --apply to execute.');
            return self::SUCCESS;
        }

        if (empty($plan)) {
            $this->warn('Nothing to apply (all groups skipped or none found).');
            return self::SUCCESS;
        }

        // ---------- CONFIRM ----------
        $this->newLine();
        if (!$this->confirm("Apply these changes to the database? A backup will be written first.", false)) {
            $this->info('Aborted. No changes made.');
            return self::SUCCESS;
        }

        // ---------- BACKUP ----------
        $backupPath = $service->writeMergeBackup(array_map(fn($g) => [
            'name'       => $g['name'],
            'survivor'   => $g['_survivorRow'],
            'duplicates' => $g['_duplicateRows'],
        ], $plan));
        $this->info("Backup written: {$backupPath}");

        // ---------- APPLY (transactional) ----------
        try {
            DB::transaction(function () use ($plan, $service) {
                foreach ($plan as $g) {
                    $service->repointDuplicatesToSurvivor($g['_survivorRow'], $g['_duplicateRows']);
                }
            });
        } catch (\Throwable $e) {
            $this->error('Transaction failed and was rolled back: ' . $e->getMessage());
            $this->error("Backup remains at: {$backupPath}");
            return self::FAILURE;
        }

        $this->newLine();
        $this->info("Done. Repointed {$totalRepoints} properties row(s), deleted {$totalDeletes} dictionary row(s).");
        $this->info("If anything looks wrong, the backup is at: {$backupPath}");
        return self::SUCCESS;
    }

    private function snippet($text, int $len = 90): string
    {
        $text = trim((string) $text);
        return mb_strlen($text) > $len ? mb_substr($text, 0, $len) . '…' : $text;
    }
}
