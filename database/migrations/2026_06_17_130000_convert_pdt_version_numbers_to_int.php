<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convert productdatatemplates.versionNumber / revisionNumber from VARCHAR(255) to
 * INT, fixing latent text-ordering bugs (e.g. "10" sorts before "9" as text, so
 * MAX()/ORDER BY/'>' on the lineage go wrong once any version reaches 10).
 *
 * Defensive, per the agreed plan:
 *   1. Re-scan for any non-integer value and ABORT before touching anything if found
 *      (the data was clean at audit time; this guards against drift).
 *   2. Back the whole table up to storage/app as JSON first.
 *   3. Convert the columns (values are already clean integers, cast explicitly).
 *   4. Add the deferred composite lineage index, now that the columns are INT.
 *
 * NOTE: MySQL DDL auto-commits, so the ALTERs cannot be wrapped in a rolling-back
 * transaction; the pre-flight scan + JSON backup are the safety net instead.
 */
return new class extends Migration
{
    public function up(): void
    {
        // 1. Pre-flight: abort if any value is not a clean non-negative integer.
        $bad = DB::table('productdatatemplates')
            ->select('Id', 'GUID', 'versionNumber', 'revisionNumber')
            ->get()
            ->filter(fn($r) => !preg_match('/^\d+$/', (string) $r->versionNumber)
                || !preg_match('/^\d+$/', (string) $r->revisionNumber));

        if ($bad->isNotEmpty()) {
            throw new \RuntimeException(
                'Aborting PDT version->int conversion: ' . $bad->count() .
                ' row(s) have non-integer version/revision. Resolve these Ids first: ' .
                $bad->pluck('Id')->implode(', ')
            );
        }

        // 2. Back up the table to storage before altering.
        $backup = [
            'generated_at' => now()->toIso8601String(),
            'table'        => 'productdatatemplates',
            'rows'         => DB::table('productdatatemplates')->get(),
        ];
        $path = storage_path('app/pdt_version_int_migration_backup_' . now()->format('Ymd_His') . '.json');
        file_put_contents($path, json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // 3. Convert columns. Existing values are clean integers; the cast is lossless.
        //    The ALTER rebuilds the table, which under strict sql_mode would reject this
        //    table's legacy '0000-00-00' date values (an existing app convention we must
        //    preserve). Relax sql_mode for just this window, then restore it. We only
        //    touch the version columns; the zero-dates pass through unchanged.
        $previousSqlMode = DB::selectOne('SELECT @@SESSION.sql_mode AS m')->m;
        DB::statement("SET SESSION sql_mode = ''");
        try {
            DB::statement('ALTER TABLE productdatatemplates MODIFY versionNumber INT NOT NULL');
            DB::statement('ALTER TABLE productdatatemplates MODIFY revisionNumber INT NOT NULL');

            // 4. Deferred composite lineage index (safe now the columns are INT).
            Schema::table('productdatatemplates', function (Blueprint $table) {
                $table->index(['GUID', 'versionNumber', 'revisionNumber'], 'pdt_lineage_idx');
            });
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$previousSqlMode]);
        }
    }

    public function down(): void
    {
        Schema::table('productdatatemplates', function (Blueprint $table) {
            $table->dropIndex('pdt_lineage_idx');
        });

        $previousSqlMode = DB::selectOne('SELECT @@SESSION.sql_mode AS m')->m;
        DB::statement("SET SESSION sql_mode = ''");
        try {
            DB::statement('ALTER TABLE productdatatemplates MODIFY versionNumber VARCHAR(255) NOT NULL');
            DB::statement('ALTER TABLE productdatatemplates MODIFY revisionNumber VARCHAR(255) NOT NULL');
        } finally {
            DB::statement('SET SESSION sql_mode = ?', [$previousSqlMode]);
        }
    }
};
