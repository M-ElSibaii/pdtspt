<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ISO 23387 (ed-2) units / physical-quantity / dimension reference layer.
 *
 * Tables derived directly from the XSD concept types:
 *   - DimensionType      -> dimensions (7 SI exponents, ISO 80000 order)
 *   - QuantityKindType   -> physical_quantities (name + language + DimensionRef)
 *   - UnitType           -> units (code/name + PhysicalQuantity link + DimensionRef +
 *                           Symbol/Scale/Base/Coefficient/Offset)
 *
 * DECISION (see task Stage 0): "tables now, physics later". Physics-bearing columns
 * (exponents, scale/base/coefficient/offset) are nullable and left empty for now; they
 * will be sourced from QUDT / ISO 80000 in a later pass. referenceURI is the QUDT anchor.
 * All PKs are stable deterministic GUIDs (dashed-UUID form), never auto-increment.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dimensions', function (Blueprint $table) {
            $table->char('guid', 36)->primary();
            // ISO 80000 order. xs:decimal in the XSD -> allow rational roots via decimal(6,3).
            $table->decimal('exp_length', 6, 3)->nullable();
            $table->decimal('exp_mass', 6, 3)->nullable();
            $table->decimal('exp_time', 6, 3)->nullable();
            $table->decimal('exp_electric_current', 6, 3)->nullable();
            $table->decimal('exp_thermodynamic_temperature', 6, 3)->nullable();
            $table->decimal('exp_amount_of_substance', 6, 3)->nullable();
            $table->decimal('exp_luminous_intensity', 6, 3)->nullable();
            // Internal canonical string e.g. "L1MT-2" (not an XSD element).
            $table->string('canonical', 191)->nullable()->unique();
            $table->string('reference_uri', 500)->nullable();
        });

        Schema::create('physical_quantities', function (Blueprint $table) {
            $table->char('guid', 36)->primary();
            // Case-/accent-sensitive to keep distinct dictionary names ("MOhm" vs "mOhm").
            $table->string('name', 191)->collation('utf8mb4_bin');
            // ISO 23386 language for the "physical quantity | language" output pairing.
            $table->string('languageIsoCode', 20)->default('en.EN');
            $table->char('dimension_guid', 36)->nullable();
            $table->string('reference_uri', 500)->nullable();
            $table->unique(['name', 'languageIsoCode']);
            $table->foreign('dimension_guid')->references('guid')->on('dimensions')->nullOnDelete();
        });

        Schema::create('units', function (Blueprint $table) {
            $table->char('guid', 36)->primary();
            // Case- AND accent-sensitive: "h" (hour) vs "H" (henry), "mW" vs "MW", etc.
            // are distinct unit codes, so the unique index must use a binary collation.
            $table->string('code', 191)->collation('utf8mb4_bin')->unique();
            $table->string('name', 500)->nullable();
            $table->char('physical_quantity_guid', 36)->nullable();
            // XSD UnitType required fields -> nullable now (physics later, no fabrication).
            $table->string('reference_uri', 500)->nullable();
            $table->string('scale', 20)->nullable();        // LINEAR | LOGARITHMIC
            $table->string('base', 10)->nullable();         // ONE | TWO | E | PI | TEN
            $table->string('coefficient', 60)->nullable();  // RationalType
            $table->string('offset', 60)->nullable();       // RationalType
            $table->foreign('physical_quantity_guid')->references('guid')->on('physical_quantities')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
        Schema::dropIfExists('physical_quantities');
        Schema::dropIfExists('dimensions');
    }
};
