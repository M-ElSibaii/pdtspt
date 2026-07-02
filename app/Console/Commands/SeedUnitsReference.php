<?php

namespace App\Console\Commands;

use App\Models\Dimension;
use App\Models\PhysicalQuantity;
use App\Models\Unit;
use App\Services\BsddEnums;
use App\Support\StableGuid;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent seeder for the ISO 23387 units / physical-quantity / dimension reference
 * layer, sourced from BsddEnums::unitsMap() (code => written-out English name).
 *
 * DECISION "tables now, physics later": we create physical_quantities (English name +
 * languageIsoCode) and units linked to them, plus the ISO 23386 unit-less "without"
 * quantity. Dimensions and unit physics (scale/base/coefficient/offset, exponents) are
 * NOT fabricated here — they stay empty until sourced from QUDT / ISO 80000.
 *
 * Re-running matches existing rows by their deterministic GUID: unchanged rows are left
 * alone, differing rows are updated, and counts are reported.
 */
class SeedUnitsReference extends Command
{
    protected $signature = 'units:seed-reference';
    protected $description = 'Seed/refresh the ISO 23387 units, physical_quantities and dimensions reference tables (idempotent).';

    /** ISO 23386 unit-less physical quantity for non-physical / text properties. */
    public const WITHOUT = 'without';
    public const DEFAULT_LANG = 'en.EN';

    public function handle(): int
    {
        $pq = ['created' => 0, 'updated' => 0, 'unchanged' => 0];
        $un = ['created' => 0, 'updated' => 0, 'unchanged' => 0];

        DB::transaction(function () use (&$pq, &$un) {
            // ISO 23386 "without" quantity (unit-less / text properties).
            $this->upsertPhysicalQuantity(self::WITHOUT, self::DEFAULT_LANG, $pq);

            foreach (BsddEnums::unitsMap() as $code => $name) {
                $code = (string) $code;
                $name = (string) $name;
                if ($code === '') {
                    continue;
                }
                $pqGuid = $this->upsertPhysicalQuantity($name, self::DEFAULT_LANG, $pq);
                $this->upsertUnit($code, $name, $pqGuid, $un);
            }
        });

        $this->info('ISO 23387 reference layer seeded (idempotent).');
        $this->table(
            ['Table', 'Created', 'Updated', 'Unchanged', 'Total rows'],
            [
                ['physical_quantities', $pq['created'], $pq['updated'], $pq['unchanged'], PhysicalQuantity::count()],
                ['units', $un['created'], $un['updated'], $un['unchanged'], Unit::count()],
                ['dimensions', 0, 0, 0, Dimension::count() . ' (physics deferred)'],
            ]
        );

        return self::SUCCESS;
    }

    private function upsertPhysicalQuantity(string $name, string $lang, array &$counts): string
    {
        $guid = StableGuid::forPhysicalQuantity($name, $lang);
        $row = PhysicalQuantity::find($guid);
        if (!$row) {
            PhysicalQuantity::create([
                'guid' => $guid,
                'name' => $name,
                'languageIsoCode' => $lang,
                'dimension_guid' => null,
            ]);
            $counts['created']++;
        } elseif ($row->name !== $name || $row->languageIsoCode !== $lang) {
            $row->update(['name' => $name, 'languageIsoCode' => $lang]);
            $counts['updated']++;
        } else {
            $counts['unchanged']++;
        }
        return $guid;
    }

    private function upsertUnit(string $code, string $name, string $pqGuid, array &$counts): void
    {
        $guid = StableGuid::forUnit($code);
        $row = Unit::find($guid);
        if (!$row) {
            Unit::create([
                'guid' => $guid,
                'code' => $code,
                'name' => $name,
                'physical_quantity_guid' => $pqGuid,
            ]);
            $counts['created']++;
        } elseif ($row->code !== $code || $row->name !== $name || $row->physical_quantity_guid !== $pqGuid) {
            $row->update(['code' => $code, 'name' => $name, 'physical_quantity_guid' => $pqGuid]);
            $counts['updated']++;
        } else {
            $counts['unchanged']++;
        }
    }
}
