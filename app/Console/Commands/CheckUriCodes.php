<?php

namespace App\Console\Commands;

use App\Services\UriService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Report every identifier the dictionary would publish, and every collision in it.
 *
 * Collisions are NOT resolved: where the scheme makes a code name-only (a property, a
 * data template, a construction object, a reference document) two records sharing a name
 * is a data defect for a human to reconcile. Renaming one automatically would hand out an
 * identifier that silently means something different from one export to the next.
 *
 *   php artisan uri:check                 report collisions in what is published (Active)
 *   php artisan uri:check --scope=all     audit every stored row, superseded versions too
 *   php artisan uri:check --sync          also rebuild the uri_codes registry
 */
class CheckUriCodes extends Command
{
    protected $signature = 'uri:check
        {--scope=export : "export" (Active rows — what is published) or "all" (every row)}
        {--sync : rebuild the uri_codes registry from the current data}';

    protected $description = 'Report PDTs.pt identifier collisions, and optionally rebuild the uri_codes registry';

    public function handle(): int
    {
        $scope = $this->option('scope') === 'all' ? 'all' : 'export';

        $this->info('Dictionary ' . UriService::dictionaryVersion() . ' — ' . UriService::dictionaryUri());
        $this->line('Scope: ' . ($scope === 'export' ? 'Active rows (what is published)' : 'every stored row'));
        $this->newLine();

        $collisions = UriService::collisions($scope);

        foreach ($this->counts($scope) as $entity => $count) {
            $this->line(sprintf('  %-10s %5d codes', $entity, $count));
        }
        $this->newLine();

        if (!$collisions) {
            $this->info('No collisions: every identifier resolves to exactly one record.');
        } else {
            $this->error(count($collisions) . ' collision(s). Each of these codes is claimed by more than one record:');
            $this->newLine();
            foreach ($collisions as $c) {
                $this->line('  <fg=yellow>' . UriService::uri($c['entity'], $c['code']) . '</>');
                foreach ($c['records'] as $record) {
                    $this->line('      ' . $record);
                }
                $this->newLine();
            }
            $this->line('Reconcile these records (Admin > Dedupe dictionary). Nothing was renamed.');
        }

        if ($this->option('sync')) {
            $this->newLine();
            $this->syncRegistry($scope, $collisions);
        }

        return $collisions ? self::FAILURE : self::SUCCESS;
    }

    /** @return array<string,int> entity => number of distinct codes */
    private function counts(string $scope): array
    {
        $out = [];
        foreach ($this->rows($scope) as $entity => $rows) {
            $out[$entity] = count(array_unique(array_column($rows, 'code')));
        }

        return $out;
    }

    /**
     * Every (entity, code, record) triple in scope.
     *
     * @return array<string,array<int,array<string,mixed>>>
     */
    private function rows(string $scope): array
    {
        $out = [];

        foreach (UriService::entities() as $entity) {
            if ($entity === UriService::ENUM_VALUE) continue; // derived from a property's inline list

            $out[$entity] = [];
            foreach ($this->sourceRows($entity, $scope) as $row) {
                try {
                    $code = UriService::codeFor($entity, $row);
                } catch (\Throwable $e) {
                    $this->warn('  ' . $entity . ': ' . $e->getMessage());
                    continue;
                }
                $out[$entity][] = [
                    'code' => $code,
                    'row' => $row,
                ];
            }
        }

        return $out;
    }

    private function sourceRows(string $entity, string $scope)
    {
        [$table, $key, $lineage, $versioned] = $this->shape($entity);

        $query = DB::table($table);
        if ($scope === 'export' && $versioned) {
            $query->where('status', 'Active');
        }

        return $query->get();
    }

    /** @return array{0:string,1:string,2:?string,3:bool} */
    private function shape(string $entity): array
    {
        $shapes = [
            UriService::PROPERTY       => ['propertiesdatadictionaries', 'Id', 'GUID', true],
            UriService::DATA_TEMPLATE  => ['productdatatemplates', 'Id', 'GUID', true],
            UriService::CONSTRUCTION   => ['constructionobjects', 'GUID', 'GUID', true],
            UriService::GROUP          => ['groupofproperties', 'Id', 'GUID', true],
            UriService::CLASS_PROPERTY => ['properties', 'Id', null, false],
            UriService::DOCUMENT       => ['referencedocuments', 'GUID', null, false],
            UriService::UNIT           => ['units', 'guid', null, false],
            UriService::QUANTITY_KIND  => ['physical_quantities', 'guid', null, false],
        ];

        return $shapes[$entity];
    }

    /**
     * Rebuild uri_codes. Colliding codes are registered once (first record wins the row)
     * and listed above — the registry records what IS, it does not decide what should be.
     */
    private function syncRegistry(string $scope, array $collisions): void
    {
        if (!Schema::hasTable('uri_codes')) {
            $this->warn('uri_codes does not exist yet — run `php artisan migrate` first.');
            return;
        }

        $version = UriService::dictionaryVersion();
        $now = now();
        $written = 0;
        $skipped = 0;

        DB::table('uri_codes')->where('dictionaryVersion', $version)->delete();

        foreach ($this->rows($scope) as $entity => $rows) {
            [$table, $key, $lineage] = $this->shape($entity);

            $seen = [];
            $batch = [];
            foreach ($rows as $r) {
                $code = $r['code'];
                if (strlen($code) > 191) {
                    // Never truncate a code: report it and leave it unregistered.
                    $this->warn("  {$entity}/{$code}: code exceeds the registry's 191 characters; not registered.");
                    $skipped++;
                    continue;
                }
                if (isset($seen[$code])) { $skipped++; continue; } // collision, already reported
                $seen[$code] = true;

                $batch[] = [
                    'entity' => $entity,
                    'code' => $code,
                    'recordTable' => $table,
                    'recordKey' => (string) ($r['row']->{$key} ?? ''),
                    'lineageGuid' => $lineage ? ($r['row']->{$lineage} ?? null) : null,
                    'dictionaryVersion' => $version,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            foreach (array_chunk($batch, 500) as $chunk) {
                DB::table('uri_codes')->insert($chunk);
                $written += count($chunk);
            }
        }

        $this->info("Registry rebuilt: {$written} identifiers registered, {$skipped} not registered"
            . ($collisions ? ' (collisions above).' : '.'));
    }
}
