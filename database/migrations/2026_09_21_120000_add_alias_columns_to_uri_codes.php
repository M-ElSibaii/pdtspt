<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Key the identifier registry on BOTH names.
 *
 * Codes are the Portuguese name: this is a Portuguese dictionary, and namePt is what
 * appears in exports, in DoPCs and in the canonical URI. A record's English name also
 * resolves, but only as an alias — it leads to the one canonical URI by a 301 and never
 * becomes a second identity.
 *
 * So each row is now marked as either the canonical code or an alias, and an alias
 * records the canonical code it points at. The existing UNIQUE (entity, code) still
 * holds across both kinds, which is what guarantees a code means exactly one record:
 * an English name that would shadow some record's Portuguese name is not registered at
 * all, and `php artisan uri:check` reports it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uri_codes', function (Blueprint $table) {
            $table->string('kind', 16)->default('canonical')->after('code');
            $table->string('canonicalCode', 191)->nullable()->after('kind');
        });
    }

    public function down(): void
    {
        Schema::table('uri_codes', function (Blueprint $table) {
            $table->dropColumn(['kind', 'canonicalCode']);
        });
    }
};
