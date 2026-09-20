<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The identifier registry: one row per published identifier, with a UNIQUE (entity, code).
 *
 * Nothing about the dictionary's own tables changes — no ids, no keys, no data. This is a
 * new, additive table whose only job is to make the uniqueness the URI scheme promises
 * enforceable at the database level: a second record claiming an existing (entity, code)
 * cannot be registered.
 *
 * It is a registry, not a source of truth: codes are always DERIVED by
 * App\Services\UriService from the record's own name. `php artisan uri:check --sync`
 * rebuilds it and reports every collision instead of resolving one silently.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uri_codes', function (Blueprint $table) {
            $table->id();

            // prop | dt | class | gop | classprop | doc | unit | pq | enum
            $table->string('entity', 32);

            // The code as it appears in the URI. Long enough for the longest name plus
            // an id prefix; 191 chars keeps the composite index inside InnoDB's limit
            // under utf8mb4 while never requiring a code to be truncated (over-long
            // codes are reported by uri:check, never silently cut).
            // Binary collation: identifiers are case-sensitive, and unit symbols rely on
            // it ("MW" megawatt vs "mW" milliwatt). MySQL's default collation would
            // wrongly treat those two units as one code.
            $table->string('code', 191)->collation('utf8mb4_bin');

            // Which row the code belongs to, so a collision report can name both sides.
            $table->string('recordTable', 64);
            $table->string('recordKey', 191);

            // The lineage GUID, where the entity has one: every version of a record
            // shares one code, and that is not a collision.
            $table->string('lineageGuid', 64)->nullable();

            $table->string('dictionaryVersion', 16);
            $table->timestamps();

            $table->unique(['entity', 'code'], 'uri_codes_entity_code_unique');
            $table->index(['recordTable', 'recordKey'], 'uri_codes_record_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uri_codes');
    }
};
