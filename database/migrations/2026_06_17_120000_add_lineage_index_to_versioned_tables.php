<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Composite "lineage" index (GUID, versionNumber, revisionNumber) used by head
 * detection, the bSDD export's "latest per GUID" selection, and the versioning
 * cascade. Pure performance — changes no data and no query results.
 *
 * Applied here to groupofproperties and propertiesdatadictionaries only (their
 * versionNumber/revisionNumber are already INT). The productdatatemplates lineage
 * index is added together with that table's varchar -> int conversion, to avoid
 * indexing columns that are about to be retyped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('groupofproperties', function (Blueprint $table) {
            $table->index(['GUID', 'versionNumber', 'revisionNumber'], 'gop_lineage_idx');
        });

        Schema::table('propertiesdatadictionaries', function (Blueprint $table) {
            $table->index(['GUID', 'versionNumber', 'revisionNumber'], 'dict_lineage_idx');
        });
    }

    public function down(): void
    {
        Schema::table('groupofproperties', function (Blueprint $table) {
            $table->dropIndex('gop_lineage_idx');
        });

        Schema::table('propertiesdatadictionaries', function (Blueprint $table) {
            $table->dropIndex('dict_lineage_idx');
        });
    }
};
