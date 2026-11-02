<?php

namespace App\Console\Commands;

use App\Services\BsddEnums;
use App\Services\QuantityKinds;
use App\Services\SiDimension;
use App\Support\StableGuid;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Builds the ISO 23387 reference layer — units, physical quantities (quantity kinds) and
 * dimensions — from the bSDD unit list (BsddEnums::unitsMap(), code => English name).
 *
 *  - every unit gets its SI dimension derived from its symbol ({@see SiDimension}) and the
 *    physical quantity it measures ({@see QuantityKinds}): mm → length (L1),
 *    N/mm² → pressure (L-1M1T-2). A unit that cannot be classified honestly keeps no
 *    quantity (and its dimension, if it has one).
 *  - one physical_quantities row per quantity kind, plus ISO 23386 "without"; the quantity's
 *    dimension is the one its units share.
 *  - units no longer in the source list are removed (their QUDT reference moves to the unit
 *    with the same name), and so are quantities no unit uses any more.
 *
 * Dry run by default; --apply writes in one transaction after a JSON backup. Idempotent:
 * a second --apply changes nothing. Run `php artisan uri:check --sync` afterwards so the
 * identifier registry follows.
 */
class SeedUnitsReference extends Command
{
    protected $signature = 'units:seed-reference {--apply : Persist changes (default is a dry run)}';
    protected $description = 'Rebuild the ISO 23387 units, physical quantities and dimensions reference tables (idempotent).';

    /** ISO 23386 unit-less physical quantity for non-physical / text properties. */
    public const WITHOUT = 'without';
    public const DEFAULT_LANG = 'en.EN';

    private const EXP = ['exp_length', 'exp_mass', 'exp_time', 'exp_electric_current',
        'exp_thermodynamic_temperature', 'exp_amount_of_substance', 'exp_luminous_intensity'];

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $charset = DB::connection()->getConfig('charset');
        if ($apply && strtolower((string) $charset) !== 'utf8mb4') {
            $this->error("Connection charset is '{$charset}', not utf8mb4. Aborting --apply to avoid corrupting unit symbols.");
            return self::FAILURE;
        }

        $plan = $this->plan();
        $this->report($plan);

        if (!$apply) {
            $this->comment('Dry run only — nothing written. Re-run with --apply to persist.');
            return self::SUCCESS;
        }

        $dir = storage_path('app/units-reconcile');
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $backup = $dir . '/reference_backup_' . date('Ymd_His') . '.json';
        file_put_contents($backup, json_encode([
            'units' => DB::table('units')->get(),
            'physical_quantities' => DB::table('physical_quantities')->get(),
            'dimensions' => DB::table('dimensions')->get(),
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        try {
            DB::transaction(fn () => $this->write($plan));
        } catch (\Throwable $e) {
            $this->error('Apply failed and was rolled back: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Applied. Backup: {$backup}");
        $this->line('Next: php artisan uri:check --sync   (refresh the identifier registry)');
        return self::SUCCESS;
    }

    /**
     * What the tables should contain.
     *
     * @return array{units: array, quantities: array, dimensions: array, unclassified: array, conflicts: array}
     */
    public function plan(): array
    {
        $units = $dimensions = $byKind = $unclassified = [];

        foreach (BsddEnums::unitsMap() as $code => $name) {
            $code = trim((string) $code);
            if ($code === '') {
                continue;
            }
            $vec = SiDimension::derive($code);
            $canonical = $vec === null ? null : SiDimension::canonical($vec);
            if ($canonical !== null) {
                $dimensions[$canonical] = $vec;
            }
            $kind = QuantityKinds::classify($code, $canonical);
            if ($kind === null) {
                $unclassified[] = ['code' => $code, 'name' => (string) $name, 'dimension' => $canonical];
            } else {
                $byKind[$kind][] = $canonical;
            }
            $units[$code] = ['name' => (string) $name, 'kind' => $kind, 'dimension' => $canonical];
        }

        // A quantity's dimension is the one its units share; disagreement is reported, and
        // the most common one wins so the result is still deterministic.
        $quantities = [self::WITHOUT => null];
        $conflicts = [];
        foreach ($byKind as $kind => $dims) {
            $counts = array_count_values(array_map(fn ($d) => $d ?? '', $dims));
            unset($counts['']);
            arsort($counts);
            $quantities[$kind] = $counts ? (string) array_key_first($counts) : null;
            if (count($counts) > 1) {
                $conflicts[$kind] = array_keys($counts);
            }
        }
        ksort($quantities);

        return compact('units', 'quantities', 'dimensions', 'unclassified', 'conflicts');
    }

    private function write(array $plan): void
    {
        foreach ($plan['dimensions'] as $canonical => $vec) {
            DB::table('dimensions')->updateOrInsert(
                ['guid' => StableGuid::forDimension($canonical)],
                ['canonical' => $canonical] + array_combine(self::EXP, $vec)
            );
        }

        $kindGuid = [];
        foreach ($plan['quantities'] as $kind => $dim) {
            $kindGuid[$kind] = StableGuid::forPhysicalQuantity($kind, self::DEFAULT_LANG);
            DB::table('physical_quantities')->updateOrInsert(
                ['guid' => $kindGuid[$kind]],
                [
                    'name' => $kind,
                    'namePt' => QuantityKinds::pt($kind),
                    'languageIsoCode' => self::DEFAULT_LANG,
                    'dimension_guid' => $dim === null ? null : StableGuid::forDimension($dim),
                ]
            );
        }

        // Units leaving the list hand their QUDT reference to the unit of the same name
        // (a repaired symbol), then go.
        $existing = DB::table('units')->get()->keyBy('code');
        $stale = $existing->reject(fn ($u, $code) => isset($plan['units'][$code]));
        $refByName = $stale->filter(fn ($u) => $u->reference_uri)->mapWithKeys(fn ($u) => [mb_strtolower((string) $u->name) => $u->reference_uri]);
        DB::table('units')->whereIn('guid', $stale->pluck('guid'))->delete();

        foreach ($plan['units'] as $code => $u) {
            $current = $existing->get($code);
            DB::table('units')->updateOrInsert(
                ['guid' => StableGuid::forUnit($code)],
                [
                    'code' => $code,
                    'name' => $u['name'],
                    'physical_quantity_guid' => $u['kind'] === null ? null : $kindGuid[$u['kind']],
                    'dimension_guid' => $u['dimension'] === null ? null : StableGuid::forDimension($u['dimension']),
                    'reference_uri' => $current->reference_uri ?? $refByName->get(mb_strtolower($u['name'])),
                ]
            );
        }

        // Quantities nothing points at any more (the old one-per-unit rows).
        DB::table('physical_quantities')->whereNotIn('guid', array_values($kindGuid))->delete();
        // Dimensions no unit derives any more (an earlier, wrong parse).
        DB::table('dimensions')->whereNotIn('canonical', array_map('strval', array_keys($plan['dimensions'])))->delete();

        \App\Services\UnitsReference::flush();
    }

    private function report(array $plan): void
    {
        $units = DB::table('units')->get()->keyBy('code');
        $quantities = DB::table('physical_quantities')->pluck('name', 'guid');

        $newUnits = array_diff_key($plan['units'], $units->all());
        $removed = $units->reject(fn ($u, $code) => isset($plan['units'][$code]));
        $relinked = 0;
        foreach ($plan['units'] as $code => $u) {
            $cur = $units->get($code);
            if ($cur && ($quantities[$cur->physical_quantity_guid] ?? null) !== $u['kind']) {
                $relinked++;
            }
        }

        $this->info(($this->option('apply') ? 'APPLY' : 'DRY-RUN') . ' — ISO 23387 reference layer');
        $this->table(['', 'Now', 'After'], [
            ['Units', $units->count(), count($plan['units']) . ' (' . count($newUnits) . ' new, ' . $removed->count() . ' removed, ' . $relinked . ' re-linked)'],
            ['Physical quantities', $quantities->count(), count($plan['quantities'])],
            ['Dimensions', DB::table('dimensions')->count(), count($plan['dimensions']) . ' derived'],
            ['Units without a quantity', DB::table('units')->whereNull('physical_quantity_guid')->count(), count($plan['unclassified'])],
        ]);
        if ($removed->isNotEmpty()) {
            $this->line('Removed (replaced by a corrected symbol or dropped from the list): ' . $removed->keys()->implode('  '));
        }
        if ($plan['unclassified']) {
            $this->line('Not classified (kept without a quantity): ' . collect($plan['unclassified'])->pluck('code')->implode('  '));
        }
        foreach ($plan['conflicts'] as $kind => $dims) {
            $this->warn("Quantity '{$kind}' has units of different dimensions: " . implode(', ', $dims) . ' (using ' . $dims[0] . ')');
        }
    }
}
