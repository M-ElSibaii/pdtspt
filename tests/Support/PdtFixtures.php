<?php

namespace Tests\Support;

use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Minimal, explicit PDT data for feature tests: one template with groups and
 * properties, written straight to the tables the services read.
 */
class PdtFixtures
{
    private static int $seq = 0;

    public static function guid(): string
    {
        return bin2hex(random_bytes(16));
    }

    public static function admin(): User
    {
        return User::forceCreate([
            'name' => 'Admin', 'email' => 'admin' . (++self::$seq) . '@example.com', 'email_verified_at' => now(),
            'password' => bcrypt('password'), 'profession' => 'x', 'institute' => 'x', 'isAdmin' => 1, 'subscribe' => 0,
        ]);
    }

    public static function user(): User
    {
        return User::forceCreate([
            'name' => 'User', 'email' => 'user' . (++self::$seq) . '@example.com', 'email_verified_at' => now(),
            'password' => bcrypt('password'), 'profession' => 'x', 'institute' => 'x', 'isAdmin' => 0, 'subscribe' => 0,
        ]);
    }

    /** The 'n/a' reference document every row defaults to. */
    public static function base(): void
    {
        if (!DB::table('referencedocuments')->where('GUID', 'n/a')->exists()) {
            DB::table('referencedocuments')->insert(['GUID' => 'n/a', 'rdName' => 'n/a', 'status' => 'Active',
                'created_at' => '2024-01-01', 'updated_at' => '2024-01-01']);
        }
    }

    public static function constructionObject(string $status = 'Active'): string
    {
        self::base();
        $guid = self::guid();
        DB::table('constructionobjects')->insert([
            'GUID' => $guid, 'constructionObjectNameEn' => 'Object ' . (++self::$seq), 'constructionObjectNamePt' => 'Objeto',
            'status' => $status, 'versionNumber' => 1, 'revisionNumber' => 0,
            'dateOfRevision' => '2024-01-01', 'dateOfVersion' => '2024-01-01', 'created_at' => '2024-01-01', 'updated_at' => '2024-01-01',
        ]);

        return $guid;
    }

    public static function dict(array $over = []): object
    {
        self::base();
        $n = ++self::$seq;
        $id = DB::table('propertiesdatadictionaries')->insertGetId(array_merge([
            'GUID' => self::guid(), 'nameEn' => "Prop{$n}", 'namePt' => "Prop{$n}", 'nameEnSc' => "Prop {$n}", 'namePtSc' => "Prop {$n}",
            'definitionEn' => "Definition {$n}", 'definitionPt' => "Definicao {$n}", 'dataType' => 'String',
            'status' => 'Active', 'versionNumber' => 1, 'revisionNumber' => 0,
            'dateOfRevision' => '2024-01-01', 'dateOfVersion' => '2024-01-01', 'created_at' => '2024-01-01', 'updated_at' => '2024-01-01',
        ], $over), 'Id');

        return DB::table('propertiesdatadictionaries')->where('Id', $id)->first();
    }

    /**
     * A published PDT: ['pdt' => row, 'gops' => [row...], 'ctx' => [gopId => [row...]], 'dicts' => [row...]].
     *
     * @param array $gops list of property counts per group, e.g. [2, 1]
     */
    public static function pdt(array $gops = [2, 1], array $over = []): array
    {
        self::base();
        $n = ++self::$seq;
        $coGuid = $over['constructionObjectGUID'] ?? self::constructionObject();
        $pdtId = DB::table('productdatatemplates')->insertGetId(array_merge([
            'GUID' => self::guid(), 'referenceDocumentGUID' => 'n/a', 'constructionObjectGUID' => $coGuid,
            'pdtNameEn' => "Template {$n}", 'pdtNamePt' => "Modelo {$n}", 'descriptionEn' => 'desc', 'descriptionPt' => 'desc',
            'category' => 'Construção', 'status' => 'Active', 'versionNumber' => 2, 'revisionNumber' => 1,
            'dateOfRevision' => '2024-01-01', 'dateOfVersion' => '2024-01-01', 'created_at' => '2024-01-01', 'updated_at' => '2024-01-01',
        ], $over), 'Id');

        return self::fill($pdtId, $gops);
    }

    /** Add groups (with fresh properties) to an existing PDT and return the full picture. */
    public static function fill(int $pdtId, array $gops): array
    {
        $pdt = DB::table('productdatatemplates')->where('Id', $pdtId)->first();
        foreach ($gops as $i => $propCount) {
            $n = ++self::$seq;
            $gopId = DB::table('groupofproperties')->insertGetId([
                'GUID' => self::guid(), 'pdtId' => $pdtId, 'referenceDocumentGUID' => 'n/a',
                'gopNameEn' => "Group {$n}", 'gopNamePt' => "Grupo {$n}", 'definitionEn' => 'g', 'definitionPt' => 'g',
                'status' => $pdt->status, 'versionNumber' => 1, 'revisionNumber' => 2,
                'dateOfRevision' => '2024-01-01', 'dateOfVersion' => '2024-01-01', 'created_at' => '2024-01-01', 'updated_at' => '2024-01-01',
            ], 'Id');
            for ($p = 0; $p < $propCount; $p++) {
                $d = self::dict(['status' => $pdt->status === 'Preview' ? 'Preview' : 'Active']);
                DB::table('properties')->insert([
                    'GUID' => $d->GUID, 'propertyId' => $d->Id, 'gopID' => $gopId, 'pdtID' => $pdtId,
                    'descriptionEn' => 'usage', 'descriptionPt' => 'uso', 'referenceDocumentGUID' => 'n/a',
                    'created_at' => '2024-01-01', 'updated_at' => '2024-01-01',
                ]);
            }
        }

        return self::load($pdtId);
    }

    public static function load(int $pdtId): array
    {
        $gops = DB::table('groupofproperties')->where('pdtId', $pdtId)->orderBy('Id')->get();
        $ctx = DB::table('properties')->where('pdtID', $pdtId)->orderBy('Id')->get();

        return [
            'pdt' => DB::table('productdatatemplates')->where('Id', $pdtId)->first(),
            'gops' => $gops->all(),
            'ctx' => $ctx->groupBy('gopID')->map->all()->all(),
            'dicts' => DB::table('propertiesdatadictionaries')->whereIn('Id', $ctx->pluck('propertyId'))->get()->all(),
        ];
    }
}
