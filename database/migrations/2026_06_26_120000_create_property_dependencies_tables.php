<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * EN ISO 23387:2025 R-23387-8 — property dependency modelling (Table 1 / 4.4.6).
 *
 * The typed, payload-bearing relation that the generic entity_relationships table cannot
 * carry, so it gets its own pair of tables (header + ordered targets):
 *
 *   property_dependencies        — one row per logical dependency of a source property,
 *                                  carrying the sub-kind and (for function) an expression
 *                                  STRING that is STORED, never executed.
 *   property_dependency_targets  — the 1..* target properties of that dependency, ordered,
 *                                  with isPreferred for the reference-vs-alternative proxy
 *                                  distinction and an optional version pin.
 *
 * Nesting (4.4.6) is represented by CHAINING, not nested rows: a targetPropertyGuid may
 * itself be the sourcePropertyGuid of its own dependencies. The resolver walks that chain.
 *
 * All identity is GUID lineage (never row Id), consistent with entity_relationships.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('property_dependencies', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('sourcePropertyGuid', 32);

            // VARCHAR (not enum) so the kind set can evolve without a migration; valid values
            // gated in PropertyDependencyService. Five Table-1 kinds:
            //   additive_context | combinative_context | reference_proxy | alternative_proxy | function
            $table->string('dependencyKind', 32);

            // function dependency only: the formula/instruction. Stored, NEVER executed.
            $table->text('expression')->nullable();

            $table->text('note')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('sourcePropertyGuid', 'propdep_source_idx');
        });

        Schema::create('property_dependency_targets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('dependencyId');
            $table->string('targetPropertyGuid', 32);

            // alternative_proxy: marks the preferred target among the alternatives.
            $table->boolean('isPreferred')->nullable();

            // ordering of targets (additive/combinative/function inputs).
            $table->integer('position')->nullable();

            // optional version pin; NULL = latest active of the target lineage.
            $table->integer('targetVersionNumber')->nullable();
            $table->integer('targetRevisionNumber')->nullable();

            $table->timestamps();

            $table->foreign('dependencyId')->references('id')->on('property_dependencies')->cascadeOnDelete();
            $table->index('dependencyId', 'propdeptgt_dep_idx');
            $table->index('targetPropertyGuid', 'propdeptgt_target_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('property_dependency_targets');
        Schema::dropIfExists('property_dependencies');
    }
};
