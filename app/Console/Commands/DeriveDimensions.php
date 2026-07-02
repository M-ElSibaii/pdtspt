<?php

namespace App\Console\Commands;

use App\Models\Dimension;
use App\Models\Unit;
use App\Services\SiDimension;
use App\Support\StableGuid;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Fix #1 — populate the dimensions table and link each physical_quantity to its dimension.
 *
 * For each unit we derive the 7 SI exponents (ISO 80000 order) by parsing the unit's physical
 * symbol (SiDimension). De-duplicated dimension rows are created with a stable deterministic
 * GUID over the canonical exponent string, and the unit's physical_quantity.dimension_guid is
 * linked. Quantities whose unit does not parse into known physical tokens are left unlinked and
 * reported (no guessing).
 *
 * The bSDD/QUDT sheet carries no dimension exponents, so all values here are DERIVED (0 sourced);
 * QUDT is still cited as external authority on the pages. Dry-run by default; --apply writes in a
 * transaction with a JSON backup and utf8mb4 guard.
 */
class DeriveDimensions extends Command
{
    protected $signature = 'dimensions:derive {--apply : Persist changes (default is a dry run)}';
    protected $description = 'Derive SI dimensions from unit symbols and link physical_quantities (idempotent).';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $charset = DB::connection()->getConfig('charset');
        if ($apply && strtolower((string) $charset) !== 'utf8mb4') {
            $this->error("Connection charset is '{$charset}', not utf8mb4. Aborting --apply.");
            return self::FAILURE;
        }

        $units = Unit::whereNotNull('physical_quantity_guid')->get();

        $dimRows = [];      // canonical => ['guid'=>, 'vec'=>]
        $pqLink = [];       // physical_quantity_guid => dimension_guid
        $unlinked = [];     // units that didn't parse
        $derived = 0;

        foreach ($units as $u) {
            $vec = SiDimension::derive($u->code);
            if ($vec === null) {
                $unlinked[] = ['code' => $u->code, 'name' => $u->name, 'pq' => $u->physical_quantity_guid];
                continue;
            }
            $derived++;
            $canonical = SiDimension::canonical($vec);
            if (!isset($dimRows[$canonical])) {
                $dimRows[$canonical] = ['guid' => StableGuid::forDimension($canonical), 'vec' => $vec];
            }
            $pqLink[$u->physical_quantity_guid] = $dimRows[$canonical]['guid'];
        }

        // What actually changes vs current DB state (idempotency accounting).
        $existingDims = DB::table('dimensions')->pluck('guid')->flip();
        $dimsToCreate = array_filter($dimRows, fn ($d) => !$existingDims->has($d['guid']));
        $currentLinks = DB::table('physical_quantities')->pluck('dimension_guid', 'guid');
        $linksToChange = [];
        foreach ($pqLink as $pq => $dg) {
            if (($currentLinks[$pq] ?? null) !== $dg) $linksToChange[$pq] = $dg;
        }

        $dir = storage_path('app/units-reconcile');
        if (!is_dir($dir)) mkdir($dir, 0775, true);
        $stamp = date('Ymd_His');
        file_put_contents("$dir/dimensions_unlinked_{$stamp}.json", json_encode($unlinked, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $this->info(($apply ? 'APPLY' : 'DRY-RUN') . ' — dimension derivation');
        $this->table(
            ['Units scanned', 'Derived', 'Unlinked (null)', 'Distinct dimensions', 'Dims to create', 'Quantity links to set'],
            [[$units->count(), $derived, count($unlinked), count($dimRows), count($dimsToCreate), count($linksToChange)]]
        );
        $this->line("Source: DERIVED from unit symbols (SiDimension). SOURCED from sheet: 0 (units.csv carries no exponents).");
        $this->line("Unlinked quantities: " . count($unlinked) . " (left null) -> $dir/dimensions_unlinked_{$stamp}.json");

        if (!$apply) {
            $this->comment('Dry run only — nothing written. Re-run with --apply to persist.');
            return self::SUCCESS;
        }

        // Backup: current dimensions + current pq links we will touch.
        $backup = [
            'dimensions' => DB::table('dimensions')->get()->toArray(),
            'links' => DB::table('physical_quantities')->whereIn('guid', array_keys($linksToChange))
                ->get(['guid', 'dimension_guid'])->toArray(),
        ];
        file_put_contents("$dir/dimensions_backup_{$stamp}.json", json_encode($backup, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        try {
            DB::transaction(function () use ($dimRows, $pqLink) {
                foreach ($dimRows as $canonical => $d) {
                    $vec = $d['vec'];
                    DB::table('dimensions')->updateOrInsert(
                        ['guid' => $d['guid']],
                        [
                            'canonical' => $canonical,
                            'exp_length' => $vec[0], 'exp_mass' => $vec[1], 'exp_time' => $vec[2],
                            'exp_electric_current' => $vec[3], 'exp_thermodynamic_temperature' => $vec[4],
                            'exp_amount_of_substance' => $vec[5], 'exp_luminous_intensity' => $vec[6],
                        ]
                    );
                }
                foreach ($pqLink as $pq => $dg) {
                    DB::table('physical_quantities')->where('guid', $pq)->update(['dimension_guid' => $dg]);
                }
            });
        } catch (\Throwable $e) {
            $this->error('Apply failed and was rolled back: ' . $e->getMessage());
            $this->line("Restore reference: $dir/dimensions_backup_{$stamp}.json");
            return self::FAILURE;
        }

        $this->info("Applied: " . count($dimsToCreate) . " dimensions created, " . count($linksToChange) . " quantity links set. Backup: $dir/dimensions_backup_{$stamp}.json");
        $this->line("Total dimensions now: " . Dimension::count() . " | quantities linked: " . DB::table('physical_quantities')->whereNotNull('dimension_guid')->count());
        return self::SUCCESS;
    }
}
