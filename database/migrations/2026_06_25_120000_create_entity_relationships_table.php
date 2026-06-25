<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EN ISO 23387:2025 R-23387-7 — generic self-referential structural relationships.
 *
 * One uniform store for the structural relations the standard applies to every core
 * entity: HasPart (0..*), IsSubtypeOf (0..1 — pdt/gop/objecttype), IsSpecializationOf
 * (0..1 — property). Property IsDependentOn (R-23387-8) is intentionally NOT modelled
 * here; it is typed/payload-bearing and gets its own tables in a later phase.
 *
 * IDENTITY: relations key on GUID lineage (stable across versions), never row Id.
 * NULL target version/revision => "latest active of that lineage" (current behaviour);
 * a pin can be set later when an exact version must be referenced. Because relations are
 * GUID-keyed, versioning a PDT (new row, same GUID) does NOT require cloning relations.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('entity_relationships', function (Blueprint $table) {
            $table->bigIncrements('id');

            $table->enum('sourceEntityType', ['pdt', 'gop', 'property', 'objecttype']);
            $table->string('sourceGuid', 32);

            // VARCHAR, NOT enum: the set of relation types will grow (IsDependentOn,
            // R-23387-8, comes in the dependency phase). Keeping this a string means new
            // types need NO migration on a populated table — valid values are gated in
            // RelationshipService::assertRelationType(). Entity-type columns stay enums
            // because the four ISO 23387 entity kinds are a genuinely closed set.
            $table->string('relationType', 32);

            $table->enum('targetEntityType', ['pdt', 'gop', 'property', 'objecttype']);
            $table->string('targetGuid', 32);

            // Optional version pin. NULL = latest active of the target lineage.
            $table->integer('targetVersionNumber')->nullable();
            $table->integer('targetRevisionNumber')->nullable();

            // Ordering for HasPart (NULL elsewhere).
            $table->integer('position')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            // No duplicate edge between the same two lineages of the same type.
            $table->unique(
                ['sourceEntityType', 'sourceGuid', 'relationType', 'targetEntityType', 'targetGuid'],
                'entity_rel_unique_edge'
            );
            $table->index(['sourceEntityType', 'sourceGuid'], 'entity_rel_source_idx');
            $table->index(['targetEntityType', 'targetGuid'], 'entity_rel_target_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('entity_relationships');
    }
};
