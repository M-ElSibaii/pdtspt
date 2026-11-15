<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;

/**
 * A small, fully fixed IsSubtypeOf chain: the master template, a parent that is a subtype of
 * the master, and a child that is a subtype of the parent. Ids, GUIDs, names and dates are all
 * literal, so the exports built from it are the same bytes on every run and on every branch.
 *
 *   master (Id 9001)  1 group  "Dados de conformidade"  2 properties
 *   parent (Id 9002)  1 group  "Dados gerais"           2 properties   IsSubtypeOf master
 *   child  (Id 9003)  2 groups "Dados gerais"           1 property     IsSubtypeOf parent
 *                              "Dados de desempenho"    3 properties
 *
 * The child's "Dados gerais" shares its Portuguese name with the parent's, so on the public
 * page both sit under one heading: the case where one heading mixes two origins.
 */
class OriginFixture
{
    public const MASTER_GUID = '230d9954097541b793f2a1fddb8bd0ad';
    public const PARENT_GUID = 'a0000000000000000000000000009002';
    public const CHILD_GUID  = 'a0000000000000000000000000009003';
    public const CO_GUID     = 'c0000000000000000000000000000001';
    public const RD_GUID     = 'd0000000000000000000000000000001';

    public const MASTER = 9001;
    public const PARENT = 9002;
    public const CHILD  = 9003;

    /** gopId => [pdtId, namePt, nameEn, property count] */
    public const GROUPS = [
        9101 => [self::MASTER, 'Dados de conformidade', 'Compliance data', 2],
        9102 => [self::PARENT, 'Dados gerais', 'General data', 2],
        9103 => [self::CHILD, 'Dados gerais', 'General data', 1],
        9104 => [self::CHILD, 'Dados de desempenho', 'Performance data', 3],
    ];

    private const D = '2024-01-01';

    public static function build(): void
    {
        PdtFixtures::base();
        DB::table('referencedocuments')->insert(['GUID' => self::RD_GUID, 'rdName' => 'EN 771-1:2011+A1:2015',
            'title' => 'Specification for masonry units', 'status' => 'Active', 'created_at' => self::D, 'updated_at' => self::D]);
        DB::table('constructionobjects')->insert([
            'GUID' => self::CO_GUID, 'referenceDocumentGUID' => 'n/a', 'constructionObjectNameEn' => 'Masonry unit',
            'constructionObjectNamePt' => 'Unidade de alvenaria', 'descriptionEn' => 'unit', 'descriptionPt' => 'unidade',
            'status' => 'Active', 'versionNumber' => 1, 'revisionNumber' => 0,
            'dateOfRevision' => self::D, 'dateOfVersion' => self::D, 'created_at' => self::D, 'updated_at' => self::D,
        ]);

        foreach ([
            [self::MASTER, self::MASTER_GUID, 'Modelo mestre', 'Master template', 5],
            [self::PARENT, self::PARENT_GUID, 'Alvenaria', 'Masonry', 1],
            [self::CHILD, self::CHILD_GUID, 'Tijolo cerâmico', 'Clay brick', 2],
        ] as [$id, $guid, $pt, $en, $v]) {
            DB::table('productdatatemplates')->insert([
                'Id' => $id, 'GUID' => $guid, 'referenceDocumentGUID' => self::RD_GUID, 'constructionObjectGUID' => self::CO_GUID,
                'pdtNamePt' => $pt, 'pdtNameEn' => $en, 'descriptionPt' => "Descrição de {$pt}", 'descriptionEn' => "Description of {$en}",
                'category' => 'Construção', 'status' => 'Active', 'versionNumber' => $v, 'revisionNumber' => 0,
                'dateOfRevision' => self::D, 'dateOfVersion' => self::D, 'created_at' => self::D, 'updated_at' => self::D,
            ]);
        }

        foreach ([[self::PARENT_GUID, self::MASTER_GUID], [self::CHILD_GUID, self::PARENT_GUID]] as [$src, $tgt]) {
            DB::table('entity_relationships')->insert([
                'sourceEntityType' => 'pdt', 'sourceGuid' => $src, 'relationType' => 'IsSubtypeOf',
                'targetEntityType' => 'pdt', 'targetGuid' => $tgt, 'created_at' => self::D, 'updated_at' => self::D,
            ]);
        }

        $n = 0;
        foreach (self::GROUPS as $gopId => [$pdtId, $pt, $en, $count]) {
            DB::table('groupofproperties')->insert([
                'Id' => $gopId, 'GUID' => sprintf('b00000000000000000000000%08d', $gopId), 'pdtId' => $pdtId,
                'referenceDocumentGUID' => 'n/a', 'gopNamePt' => $pt, 'gopNameEn' => $en,
                'definitionPt' => "Grupo {$pt}", 'definitionEn' => "Group {$en}",
                'status' => 'Active', 'versionNumber' => 1, 'revisionNumber' => 0,
                'dateOfRevision' => self::D, 'dateOfVersion' => self::D, 'created_at' => self::D, 'updated_at' => self::D,
            ]);
            for ($i = 0; $i < $count; $i++) {
                $n++;
                $dictId = 9200 + $n;
                $guid = sprintf('e00000000000000000000000%08d', $dictId);
                DB::table('propertiesdatadictionaries')->insert([
                    'Id' => $dictId, 'GUID' => $guid, 'namePt' => "PropriedadeTeste{$n}", 'nameEn' => "TestProperty{$n}",
                    'namePtSc' => "Propriedade teste {$n}", 'nameEnSc' => "Test property {$n}",
                    'definitionPt' => "Definição {$n}", 'definitionEn' => "Definition {$n}",
                    'dataType' => $n % 2 ? 'Real' : 'String', 'units' => $n % 2 ? 'mm' : null,
                    'status' => 'Active', 'versionNumber' => 1, 'revisionNumber' => 0,
                    'dateOfRevision' => self::D, 'dateOfVersion' => self::D, 'created_at' => self::D, 'updated_at' => self::D,
                ]);
                DB::table('properties')->insert([
                    'Id' => 9300 + $n, 'GUID' => $guid, 'propertyId' => $dictId, 'gopID' => $gopId, 'pdtID' => $pdtId,
                    'descriptionPt' => "Uso {$n}", 'descriptionEn' => "Usage {$n}",
                    'referenceDocumentGUID' => $n === 1 ? self::RD_GUID : 'n/a',
                    'created_at' => self::D, 'updated_at' => self::D,
                ]);
            }
        }
    }
}
