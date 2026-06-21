<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Migrate constructionobjects.status from the legacy 'Current' vocabulary to the system
 * 3-state rule (Active / Preview / InActive), so COs share the same lifecycle as PDT/GOP/
 * dict. Published COs ('Current') become 'Active'.
 *
 * Dry-run by default (reports the exact counts and any unexpected status values); --apply
 * writes inside a transaction after a JSON backup — same safety pattern as the other
 * pdts:* maintenance commands. Recompute (CO Active-if-referenced-by-active) is handled
 * separately by pdts:recompute-status once COs are folded in.
 */
class MigrateConstructionObjectStatus extends Command
{
    protected $signature = 'pdts:migrate-co-status {--apply : Persist the conversion (otherwise dry run)}';

    protected $description = "Convert constructionobjects.status 'Current' -> 'Active' (3-state vocabulary).";

    /** Legacy -> 3-state mapping. */
    private const MAP = ['Current' => 'Active'];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? '=== APPLY MODE ===' : '=== DRY RUN (no changes) ===');

        // Report the current distribution.
        $dist = DB::table('constructionobjects')
            ->select('status', DB::raw('count(*) as c'))
            ->groupBy('status')->orderByDesc('c')->get();

        $this->line('Current status distribution:');
        foreach ($dist as $d) {
            $this->line(sprintf('   %-12s %d', $d->status ?? 'NULL', $d->c));
        }

        // Rows that will convert.
        $toConvert = DB::table('constructionobjects')
            ->whereIn('status', array_keys(self::MAP))->get();

        // Flag anything outside the known mapping AND not already 3-state (for visibility).
        $known = array_merge(array_keys(self::MAP), ['Active', 'Preview', 'InActive']);
        $unexpected = DB::table('constructionobjects')
            ->where(function ($q) use ($known) {
                $q->whereNotIn('status', $known)->orWhereNull('status');
            })->get();

        $this->newLine();
        foreach (self::MAP as $from => $to) {
            $n = $toConvert->where('status', $from)->count();
            $this->info("Will convert '{$from}' -> '{$to}': {$n} row(s).");
        }
        if ($unexpected->isNotEmpty()) {
            $this->warn($unexpected->count() . " row(s) have a status outside {Current,Active,Preview,InActive} or NULL — LEFT UNCHANGED:");
            foreach ($unexpected->take(20) as $r) {
                $this->line("   GUID={$r->GUID}  status=" . ($r->status ?? 'NULL') . "  '{$r->constructionObjectNameEn}'");
            }
        }

        if ($toConvert->isEmpty()) {
            $this->info('Nothing to convert.');
            return self::SUCCESS;
        }

        if (!$apply) {
            $this->newLine();
            $this->info('Dry run complete. Re-run with --apply to persist.');
            return self::SUCCESS;
        }

        // Backup affected rows before writing.
        $backup = ['generated_at' => now()->toIso8601String(), 'table' => 'constructionobjects',
            'mapping' => self::MAP, 'rows' => $toConvert];
        $path = storage_path('app/co_status_migration_backup_' . now()->format('Ymd_His') . '.json');
        file_put_contents($path, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $this->info('Backup written: ' . basename($path));

        try {
            DB::transaction(function () {
                foreach (self::MAP as $from => $to) {
                    DB::table('constructionobjects')->where('status', $from)->update(['status' => $to]);
                }
            });
        } catch (\Throwable $e) {
            $this->error('Failed and rolled back: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info('Applied. COs now use the 3-state vocabulary.');
        return self::SUCCESS;
    }
}
