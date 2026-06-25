<?php

namespace App\Console\Commands;

use App\Models\EntityRelationship;
use App\Services\RelationshipException;
use App\Services\RelationshipService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Seed the generic relationship store with the master PDT relation that was previously
 * synthesized at export time (EN ISO 23387:2025 R-23387-7, Phase 1).
 *
 * For every non-master PDT lineage (distinct GUID), creates one
 *   IsSubtypeOf : pdt:<childGuid> -> pdt:<masterGuid>
 * via RelationshipService (idempotent, guarded). Dry run by default; --apply persists.
 */
class SeedMasterRelations extends Command
{
    protected $signature = 'relationships:seed-master {--apply : Persist the relations (otherwise dry run)}';

    protected $description = 'Seed IsSubtypeOf relations from every non-master PDT lineage to the master PDT lineage.';

    /** Must match ProductdatatemplatesController::MASTER_PDT_GUID. */
    private const MASTER_PDT_GUID = '230d9954097541b793f2a1fddb8bd0ad';

    public function handle(RelationshipService $service): int
    {
        $apply = (bool) $this->option('apply');
        $this->info($apply ? '=== APPLY MODE ===' : '=== DRY RUN (no changes will be written) ===');

        // The master lineage must exist for the relation target to be valid.
        if (!DB::table('productdatatemplates')->where('GUID', self::MASTER_PDT_GUID)->exists()) {
            $this->error('Master PDT lineage not found; nothing to seed.');
            return self::FAILURE;
        }

        // Distinct non-master PDT lineages (one relation per lineage GUID, not per version).
        $childGuids = DB::table('productdatatemplates')
            ->where('GUID', '!=', self::MASTER_PDT_GUID)
            ->distinct()
            ->pluck('GUID');

        $this->info("Found {$childGuids->count()} non-master PDT lineage(s).");

        if (!$apply) {
            $this->info('Dry run complete. Re-run with --apply to persist.');
            return self::SUCCESS;
        }

        $created = 0;
        $skipped = 0;
        DB::beginTransaction();
        try {
            foreach ($childGuids as $guid) {
                $before = EntityRelationship::where([
                    'sourceEntityType' => EntityRelationship::TYPE_PDT,
                    'sourceGuid'       => $guid,
                    'relationType'     => EntityRelationship::REL_IS_SUBTYPE_OF,
                    'targetEntityType' => EntityRelationship::TYPE_PDT,
                    'targetGuid'       => self::MASTER_PDT_GUID,
                ])->exists();

                $service->relate(
                    EntityRelationship::TYPE_PDT,
                    $guid,
                    EntityRelationship::REL_IS_SUBTYPE_OF,
                    EntityRelationship::TYPE_PDT,
                    self::MASTER_PDT_GUID
                );

                $before ? $skipped++ : $created++;
            }
            DB::commit();
        } catch (RelationshipException $e) {
            DB::rollBack();
            $this->error('Failed and rolled back: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Created {$created} relation(s); {$skipped} already existed.");
        return self::SUCCESS;
    }
}
