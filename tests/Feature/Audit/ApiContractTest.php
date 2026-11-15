<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\PdtFixtures as F;

/*
 * The contract other systems read (Dictagon: /api; bSDD: the domain JSON; everyone: the
 * URIs). Key sets are the ones the code produced when this audit started (the table
 * columns plus the appended "uri"); a change here breaks consumers, so it must be a
 * deliberate decision, not a side effect.
 */

const AUDIT_DICT_KEYS = ['Id', 'GUID', 'namePt', 'namePtSc', 'nameEn', 'nameEnSc', 'definitionPt', 'definitionEn', 'status',
    'dateOfCreation', 'dateofActivation', 'dateOfLastChange', 'dateOfRevision', 'dateOfVersion', 'versionNumber', 'revisionNumber',
    'listOfReplacedProperties', 'listOfReplacingProperties', 'relationToOtherDataDictionaries', 'creatorsLanguage',
    'visualRepresentation', 'countryOfUse', 'countryOfOrigin', 'physicalQuantity', 'dimension', 'dataType', 'dynamicProperty',
    'parametersOfTheDynamicProperty', 'units', 'namesOfDefiningValues', 'definingValues', 'tolerance', 'digitalFormat',
    'textFormat', 'listOfPossibleValuesInLanguageN', 'boundaryValues', 'updated_at', 'created_at', 'depreciationExplanation',
    'depreciationDate', 'uri'];
const AUDIT_PDT_KEYS = ['Id', 'GUID', 'referenceDocumentGUID', 'pdtNameEn', 'pdtNamePt', 'dateOfRevision', 'dateOfVersion',
    'updated_at', 'created_at', 'status', 'category', 'versionNumber', 'revisionNumber', 'descriptionEn', 'descriptionPt',
    'constructionObjectGUID', 'depreciationExplanation', 'depreciationDate', 'uri'];
const AUDIT_GOP_KEYS = ['Id', 'GUID', 'pdtId', 'referenceDocumentGUID', 'gopNameEn', 'gopNamePt', 'definitionEn', 'definitionPt',
    'status', 'dateOfCreation', 'dateofActivation', 'dateOfLastChange', 'dateOfRevision', 'dateOfVersion', 'versionNumber',
    'revisionNumber', 'listOfReplacedProperties', 'listOfReplacingProperties', 'relationToOtherDataDictionaries', 'creatorsLanguage',
    'visualRepresentation', 'countryOfUse', 'countryOfOrigin', 'categoryOfGroupOfProperties', 'parentGroupOfProperties',
    'updated_at', 'created_at', 'depreciationExplanation', 'depreciationDate', 'uri'];
const AUDIT_RD_KEYS = ['GUID', 'rdName', 'title', 'description', 'status', 'updated_at', 'created_at', 'uri'];
const AUDIT_CO_KEYS = ['GUID', 'referenceDocumentGUID', 'constructionObjectNameEn', 'constructionObjectNamePt', 'dateOfRevision',
    'dateOfVersion', 'updated_at', 'created_at', 'status', 'versionNumber', 'revisionNumber', 'descriptionEn', 'descriptionPt', 'uri'];

/** The property fields Dictagon reads. */
const AUDIT_DICTAGON_FIELDS = ['Id', 'GUID', 'uri', 'namePt', 'nameEn', 'namePtSc', 'nameEnSc', 'definitionPt', 'definitionEn',
    'dataType', 'units', 'dimension', 'physicalQuantity', 'status', 'versionNumber', 'revisionNumber', 'dateOfVersion'];

beforeEach(function () {
    Http::preventStrayRequests();
    $this->tpl = F::pdt([2, 1], ['pdtNameEn' => 'Clay brick', 'pdtNamePt' => 'Tijolo cerâmico']);
    $this->prop = $this->tpl['dicts'][0];
    DB::table('propertiesdatadictionaries')->where('Id', $this->prop->Id)->update([
        'namePt' => 'ResistenciaTermica', 'nameEn' => 'ThermalResistance', 'namePtSc' => 'Resistência térmica',
        'nameEnSc' => 'Thermal resistance', 'dataType' => 'Real', 'units' => 'm²·K/W',
    ]);
    $this->prop = DB::table('propertiesdatadictionaries')->where('Id', $this->prop->Id)->first();
});

function audit_keys(array $row): array
{
    $k = array_keys($row);
    sort($k);

    return $k;
}

function audit_sorted(array $k): array
{
    sort($k);

    return $k;
}

// ---------------------------------------------------------------- Dictagon /api

test('collection endpoints answer 200 with the stable key set', function (string $path, array $keys) {
    $res = $this->getJson($path)->assertOk();
    $rows = $res->json();
    expect($rows)->toBeArray()->not->toBeEmpty();
    foreach ($rows as $row) {
        expect(audit_keys($row))->toBe(audit_sorted($keys), $path);
    }
})->with([
    'productDataTemplates' => ['/api/productDataTemplates', AUDIT_PDT_KEYS],
    'dataDictionary' => ['/api/dataDictionary', AUDIT_DICT_KEYS],
    'groupsOfProperties' => ['/api/groupsOfProperties', AUDIT_GOP_KEYS],
    'referenceDocuments' => ['/api/referenceDocuments', AUDIT_RD_KEYS],
    'constructionObjects' => ['/api/constructionObjects', AUDIT_CO_KEYS],
]);

test('a dictionary property carries every field Dictagon reads, with the right values', function () {
    $row = $this->getJson('/api/dataDictionary/' . $this->prop->Id)->assertOk()->json();
    expect(audit_keys($row))->toBe(audit_sorted(AUDIT_DICT_KEYS));
    foreach (AUDIT_DICTAGON_FIELDS as $f) {
        expect(array_key_exists($f, $row))->toBeTrue($f);
    }
    expect($row['GUID'])->toBe($this->prop->GUID)
        ->and($row['uri'])->toBe('https://pdts.pt/uri/0.1/prop/ResistenciaTermica')
        ->and($row['namePt'])->toBe('ResistenciaTermica')->and($row['nameEn'])->toBe('ThermalResistance')
        ->and($row['dataType'])->toBe('Real')->and($row['units'])->toBe('m²·K/W')
        ->and((int) $row['versionNumber'])->toBe(1)->and((int) $row['revisionNumber'])->toBe(0)
        ->and($row['dateOfVersion'])->toStartWith('2024-01-01');

    $list = collect($this->getJson('/api/dataDictionary')->json())->firstWhere('Id', $this->prop->Id);
    expect($list)->toBe($row);
});

test('single group and reference-document endpoints answer with the stable key set', function () {
    $gop = $this->tpl['gops'][0];
    expect(audit_keys($this->getJson('/api/groupsOfProperties/' . $gop->Id)->assertOk()->json()))->toBe(audit_sorted(AUDIT_GOP_KEYS));
    $rd = F::guid();
    DB::table('referencedocuments')->insert(['GUID' => $rd, 'rdName' => 'EN 771-1', 'title' => 't', 'description' => 'd', 'status' => 'Current']);
    expect(audit_keys($this->getJson('/api/referenceDocuments/' . $rd)->assertOk()->json()))->toBe(audit_sorted(AUDIT_RD_KEYS));
});

test('the template endpoints answer 200: raw, ISO 23387 JSON and XML', function () {
    $id = $this->tpl['pdt']->Id;
    $raw = $this->getJson("/api/{$id}")->assertOk()->json();
    expect($raw)->toHaveKey('Library');
    expect($raw['Library'])->toHaveKeys(['DataTemplates', 'GroupOfProperties', 'Properties']);

    $json = $this->getJson("/api/{$id}/json")->assertOk()->json();
    expect($json['Library']['DataTemplates'][0])->toHaveKeys(['Name', 'Definition', 'HasPropertyRef', '_uri']);

    $xml = $this->get("/api/{$id}/xml")->assertOk();
    expect($xml->headers->get('Content-Type'))->toContain('application/xml');
    expect(simplexml_load_string($xml->getContent()))->not->toBeFalse();
});

test('an unknown template answers 404, never 500', function () {
    $this->getJson('/api/999999')->assertNotFound();
    $this->getJson('/api/999999/json')->assertNotFound();
    $this->get('/api/999999/xml')->assertNotFound();
});

// ---------------------------------------------------------------- ISO 23387 JSON export

test('the ISO 23387 JSON: every HasPropertyRef is in Library.Properties, each complete', function () {
    $res = $this->post(route('pdt.export.json', ['pdtId' => $this->tpl['pdt']->Id]))->assertOk();
    $body = $res->baseResponse instanceof \Symfony\Component\HttpFoundation\StreamedResponse ? $res->streamedContent() : $res->getContent();
    $doc = json_decode($body, true);
    expect(json_last_error())->toBe(JSON_ERROR_NONE);

    $lib = $doc['Library'];
    $props = collect($lib['Properties'])->keyBy('dt:GUID');
    $refs = collect($lib['DataTemplates'][0]['HasPropertyRef'])->pluck('dt:GUID');
    expect($refs)->toHaveCount(3);
    foreach ($refs as $g) {
        expect($props->has($g))->toBeTrue("HasPropertyRef {$g} missing from Library.Properties");
    }
    foreach ($lib['GroupOfProperties'] as $gop) {
        foreach ($gop['HasPropertyRef'] ?? [] as $r) {
            expect($props->has($r['dt:GUID']))->toBeTrue();
        }
    }
    foreach ($props as $p) {
        expect($p['dt:GUID'])->toMatch('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/')
            ->and($p['_uri'])->toStartWith('https://pdts.pt/uri/0.1/prop/')
            ->and(collect($p['Name'])->pluck('language')->sort()->values()->all())->toBe(['en', 'pt'])
            ->and($p['DataType']['name'] ?? null)->not->toBeEmpty();
    }
    $tr = $props->first(fn($p) => $p['_uri'] === 'https://pdts.pt/uri/0.1/prop/ResistenciaTermica');
    expect($tr)->not->toBeNull();
    expect($tr['DataType']['name'])->toBe('REAL');
});

// ---------------------------------------------------------------- bSDD domain export

test('the bSDD export follows the bSDD JSON import model', function () {
    $res = $this->actingAs(F::admin())->post('/exportdomainbsdd-psets')->assertOk();
    $doc = json_decode($res->streamedContent(), true);
    expect(json_last_error())->toBe(JSON_ERROR_NONE);

    // Dictionary level: required fields; UseOwnUri=true requires DictionaryUri.
    expect($doc)->toHaveKeys(['ModelVersion', 'OrganizationCode', 'DictionaryCode', 'DictionaryVersion', 'LanguageIsoCode',
        'LanguageOnly', 'UseOwnUri', 'DictionaryName', 'DictionaryUri', 'Classes', 'Properties']);
    expect($doc['UseOwnUri'])->toBeTrue()->and($doc['DictionaryUri'])->not->toBeEmpty();

    $units = \App\Services\BsddEnums::units();
    $codes = [];
    foreach ($doc['Properties'] as $p) {
        expect($p['Code'] ?? '')->not->toBeEmpty()
            ->and($p['Name'] ?? '')->not->toBeEmpty()
            ->and($p['DataType'])->toBeIn(['Boolean', 'Character', 'Integer', 'Real', 'String', 'Time'])
            ->and($p['OwnedUri'])->toStartWith('https://pdts.pt/uri/0.1/prop/')
            ->and($p['Status'])->toBeIn(['Active', 'Inactive'])
            ->and($p['Units'])->toBeArray();
        foreach ($p['Units'] as $u) {
            expect($u)->toBeIn($units);
        }
        foreach (['ActivationDateUtc', 'VersionDateUtc', 'RevisionDateUtc'] as $d) {
            if ($p[$d] !== null) {
                expect($p[$d])->toMatch('/^\d{4}-\d{2}-\d{2}/');
            }
        }
        $codes[] = $p['Code'];
    }
    $tr = collect($doc['Properties'])->firstWhere('Code', 'ResistenciaTermica');
    expect($tr['DataType'])->toBe('Real')->and($tr['Units'])->toBe(['m²·K/W'])->and($tr['Uid'])->toBe($this->prop->GUID);

    foreach ($doc['Classes'] as $c) {
        expect($c['Code'] ?? '')->not->toBeEmpty()
            ->and($c['Name'] ?? '')->not->toBeEmpty()
            ->and($c['ClassType'])->toBeIn(['Class', 'Material', 'GroupOfProperties', 'AlternativeUse'])
            ->and($c['OwnedUri'] ?? '')->toStartWith('https://pdts.pt/uri/0.1/');
        foreach ($c['ClassProperties'] ?? [] as $cp) {
            expect($cp['PropertyCode'])->toBeIn($codes);
        }
    }
    Http::assertNothingSent();
});

test('the bSDD export sends only bSDD unit codes, even for hand-typed units', function () {
    // bSDD import model: Property.Units is a list of codes from the bSDD units list
    // (resources/data/bsdd_enumerations.json mirrors it). Stored legacy spellings such as
    // "m2" or "W/m²K" are passed through unchanged today.
    DB::table('propertiesdatadictionaries')->where('Id', $this->prop->Id)->update(['units' => 'm2']);
    $doc = json_decode($this->actingAs(F::admin())->post('/exportdomainbsdd-psets')->streamedContent(), true);
    $p = collect($doc['Properties'])->firstWhere('Code', 'ResistenciaTermica');
    foreach ($p['Units'] as $u) {
        expect($u)->toBeIn(\App\Services\BsddEnums::units());
    }
})->skip('Not fixed: stored units are exported raw. Mapping legacy spellings to bSDD codes is listed under Decisions for Mohamad.');

test('the bSDD export is admin only', function () {
    $this->post('/exportdomainbsdd-psets')->assertRedirect(route('login'));
    $this->actingAs(F::user())->post('/exportdomainbsdd-psets')->assertRedirect('/');
});

// ---------------------------------------------------------------- URIs

test('a property URI resolves as a page and as JSON-LD with its @id', function () {
    $this->get('/uri/0.1/prop/ResistenciaTermica')->assertOk()->assertSee('ResistenciaTermica');
    $this->getJson('/uri/0.1/prop/ResistenciaTermica')->assertOk()
        ->assertJsonPath('@id', 'https://pdts.pt/uri/0.1/prop/ResistenciaTermica')
        ->assertJsonPath('record.GUID', $this->prop->GUID);
    $this->get('/uri/0.1/prop/ResistenciaTermica.json')->assertOk()
        ->assertJsonPath('@id', 'https://pdts.pt/uri/0.1/prop/ResistenciaTermica');
    $this->get('/uri/0.1/prop/ResistenciaTermica/v1?format=json')->assertOk()
        ->assertJsonPath('pinnedUri', 'https://pdts.pt/uri/0.1/prop/ResistenciaTermica/v1');
});

test('the English name points to the same @id as the Portuguese one', function () {
    $res = $this->get('/uri/0.1/prop/ThermalResistance?format=json');
    $res->assertStatus(301);
    expect($res->headers->get('Location'))->toEndWith('/uri/0.1/prop/ResistenciaTermica?format=json');
    $this->get($res->headers->get('Location'))->assertOk()
        ->assertJsonPath('@id', 'https://pdts.pt/uri/0.1/prop/ResistenciaTermica');
});

test('the template URI resolves and matches the API "uri"', function () {
    $uri = $this->getJson('/api/productDataTemplates')->collect()->firstWhere('Id', $this->tpl['pdt']->Id)['uri'];
    expect($uri)->toBe('https://pdts.pt/uri/0.1/dt/TijoloCeramico');
    $this->getJson(parse_url($uri, PHP_URL_PATH))->assertOk()->assertJsonPath('@id', $uri);
});

test('unknown names, entities and dictionary versions answer 404, not 500', function () {
    $this->get('/uri/0.1/prop/NoSuchPropertyAnywhere')->assertNotFound();
    $this->getJson('/uri/0.1/prop/NoSuchPropertyAnywhere')->assertNotFound();
    $this->get('/uri/0.1/nosuchentity/X')->assertNotFound();
    $this->get('/uri/9.9/prop/ResistenciaTermica')->assertNotFound();
    $this->get('/uri/0.1/prop/ResistenciaTermica/v99')->assertNotFound();
});

// ---------------------------------------------------------------- master template 67

test('master template 67 holds the digital product passport URL in "Dados de conformidade"', function () {
    // Fixture copying the structure described for production: template Id 67, the master
    // lineage, group "Dados de conformidade" / "Compliance data", and the property
    // UrlDoPassaporteDigitalDoProduto / DigitalProductPassportUrl.
    F::base();
    $co = F::constructionObject();
    DB::table('productdatatemplates')->insert([
        'Id' => 67, 'GUID' => '230d9954097541b793f2a1fddb8bd0ad', 'referenceDocumentGUID' => 'n/a', 'constructionObjectGUID' => $co,
        'pdtNameEn' => 'Master', 'pdtNamePt' => 'Mestre', 'descriptionEn' => 'Master', 'descriptionPt' => 'Mestre',
        'category' => 'Construção', 'status' => 'Active', 'versionNumber' => 5, 'revisionNumber' => 0,
        'dateOfRevision' => '2025-01-01', 'dateOfVersion' => '2025-01-01', 'created_at' => '2025-01-01', 'updated_at' => '2025-01-01',
    ]);
    $gopId = DB::table('groupofproperties')->insertGetId([
        'GUID' => F::guid(), 'pdtId' => 67, 'referenceDocumentGUID' => 'n/a',
        'gopNameEn' => 'Compliance data', 'gopNamePt' => 'Dados de conformidade', 'definitionEn' => 'c', 'definitionPt' => 'c',
        'status' => 'Active', 'versionNumber' => 1, 'revisionNumber' => 0,
        'dateOfRevision' => '2025-01-01', 'dateOfVersion' => '2025-01-01', 'created_at' => '2025-01-01', 'updated_at' => '2025-01-01',
    ], 'Id');
    $dpp = F::dict(['namePt' => 'UrlDoPassaporteDigitalDoProduto', 'nameEn' => 'DigitalProductPassportUrl',
        'namePtSc' => 'URL do passaporte digital do produto', 'nameEnSc' => 'Digital product passport URL', 'dataType' => 'String']);
    DB::table('properties')->insert(['GUID' => $dpp->GUID, 'propertyId' => $dpp->Id, 'gopID' => $gopId, 'pdtID' => 67,
        'descriptionEn' => 'url', 'descriptionPt' => 'url', 'referenceDocumentGUID' => 'n/a']);
    $uri = 'https://pdts.pt/uri/0.1/prop/UrlDoPassaporteDigitalDoProduto';

    $pdt = collect($this->getJson('/api/productDataTemplates')->json())->firstWhere('Id', 67);
    expect($pdt['GUID'])->toBe('230d9954097541b793f2a1fddb8bd0ad')->and($pdt['pdtNamePt'])->toBe('Mestre');

    $lib = $this->getJson('/api/67/json')->assertOk()->json('Library');
    $group = collect($lib['GroupOfProperties'])->first(
        fn($g) => collect($g['Name'])->contains(fn($n) => $n['value'] === 'Dados de conformidade')
    );
    expect($group)->not->toBeNull();
    expect(collect($group['Name'])->pluck('value'))->toContain('Compliance data');
    $prop = collect($lib['Properties'])->firstWhere('_uri', $uri);
    expect($prop)->not->toBeNull();
    expect(collect($prop['Name'])->pluck('value')->all())->toContain('UrlDoPassaporteDigitalDoProduto', 'DigitalProductPassportUrl');
    expect(collect($group['HasPropertyRef'])->pluck('dt:GUID'))->toContain($prop['dt:GUID']);

    expect(collect($this->getJson('/api/dataDictionary')->json())->firstWhere('Id', $dpp->Id)['uri'])->toBe($uri);
    $this->getJson('/uri/0.1/prop/UrlDoPassaporteDigitalDoProduto')->assertOk()->assertJsonPath('@id', $uri);
    $en = $this->get('/uri/0.1/prop/DigitalProductPassportUrl');
    $en->assertStatus(301);
    expect($en->headers->get('Location'))->toEndWith('/uri/0.1/prop/UrlDoPassaporteDigitalDoProduto');
});
