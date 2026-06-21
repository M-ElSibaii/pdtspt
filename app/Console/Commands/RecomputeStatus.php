<?php

namespace App\Console\Commands;

use App\Services\VersioningService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Recompute every element's status from scratch using the 3-state rule
 * (Active > Preview > InActive), re-baselining drift. Dry-run by default; --apply
 * writes the changes inside a transaction after a JSON backup — same safety pattern
 * as pdts:dedupe-dictionary.
 */
class RecomputeStatus extends Command
{
    protected $signature = 'pdts:recompute-status {--apply : Persist the recomputed statuses (otherwise dry run)}';

    protected $description = 'Recompute Active/Preview/InActive status for PDTs, GOPs and dictionary properties from scratch.';

    public function handle(VersioningService $service): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? '=== APPLY MODE ===' : '=== DRY RUN (no changes will be written) ===');

        // Compute without writing first, so the report and backup match exactly.
        $changes = $service->recomputeStatuses(false);

        if (empty($changes)) {
            $this->info('All statuses already consistent. Nothing to change.');
            return self::SUCCESS;
        }

        $byTable = collect($changes)->groupBy('table');
        foreach ($byTable as $table => $rows) {
            $this->line("── {$table}: {$rows->count()} change(s) ──");
            foreach ($rows->take(50) as $c) {
                $this->line(sprintf('   Id=%s  %s → %s', $c['id'], $c['from'] ?? 'NULL', $c['to']));
            }
            if ($rows->count() > 50) {
                $this->line('   … ' . ($rows->count() - 50) . ' more');
            }
        }
        $this->info('TOTAL: ' . count($changes) . ' status change(s).');

        if (!$apply) {
            $this->newLine();
            $this->info('Dry run complete. Re-run with --apply to persist.');
            return self::SUCCESS;
        }

        // Backup affected rows before writing.
        $backup = ['generated_at' => now()->toIso8601String(), 'changes' => $changes];
        $path = storage_path('app/recompute_status_backup_' . now()->format('Ymd_His') . '.json');
        file_put_contents($path, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info("Backup written: " . basename($path));

        try {
            DB::transaction(fn() => $service->recomputeStatuses(true));
        } catch (\Throwable $e) {
            $this->error('Failed and rolled back: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Applied ' . count($changes) . ' status change(s).');
        return self::SUCCESS;
    }
}
