<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * - physical_quantities.namePt: Portuguese label shown on the site next to the English name
 *   (the English name stays the identifier and the ISO 23386 export value).
 * - units.dimension_guid: the unit's own SI dimension. Most units reach their dimension
 *   through their quantity, but a unit can have a known dimension and no named quantity
 *   (A/J, V/K); this keeps that link instead of dropping it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('physical_quantities', function (Blueprint $table) {
            $table->string('namePt', 191)->nullable()->after('name');
        });
        Schema::table('units', function (Blueprint $table) {
            $table->char('dimension_guid', 36)->nullable()->after('physical_quantity_guid');
            $table->foreign('dimension_guid')->references('guid')->on('dimensions')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('units', function (Blueprint $table) {
            $table->dropForeign(['dimension_guid']);
            $table->dropColumn('dimension_guid');
        });
        Schema::table('physical_quantities', function (Blueprint $table) {
            $table->dropColumn('namePt');
        });
    }
};
