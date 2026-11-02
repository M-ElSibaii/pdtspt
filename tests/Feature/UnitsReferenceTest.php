<?php

use App\Services\QuantityKinds;
use App\Services\SiDimension;
use App\Services\UnitsReference;
use App\Services\UriService;
use Illuminate\Support\Facades\DB;
use Tests\Support\PdtFixtures as F;

/*
 * The units / physical quantities / dimensions reference layer: real quantity kinds, units
 * typed by hand still resolve, every unit/quantity/dimension page is reachable, and old
 * identifiers redirect instead of 404ing.
 */

beforeEach(function () {
    $this->backupsBefore = glob(storage_path('app/units-reconcile/reference_backup_*.json')) ?: [];
    $this->artisan('units:seed-reference', ['--apply' => true])->assertSuccessful();
    UnitsReference::flush();
});

// --apply writes a backup into storage; keep only the ones that were there before the test.
afterEach(function () {
    foreach (array_diff(glob(storage_path('app/units-reconcile/reference_backup_*.json')) ?: [], $this->backupsBefore) as $file) {
        @unlink($file);
    }
});

function pqOf(string $code): ?string
{
    return DB::table('units as u')->join('physical_quantities as q', 'q.guid', '=', 'u.physical_quantity_guid')
        ->whereRaw('BINARY u.code = ?', [$code])->value('q.name');
}

test('units are grouped under real physical quantities, not one quantity per unit', function () {
    expect(pqOf('mm'))->toBe('length')
        ->and(pqOf('km'))->toBe('length')
        ->and(pqOf('N/mm²'))->toBe('pressure')
        ->and(pqOf('MPa'))->toBe('pressure')
        ->and(pqOf('W/(m·K)'))->toBe('thermal conductivity')
        ->and(pqOf('W/(m²·K)'))->toBe('thermal transmittance')
        ->and(pqOf('kg/m³'))->toBe('density')
        ->and(pqOf('Nm'))->toBe('torque')
        ->and(pqOf('J'))->toBe('energy')
        ->and(pqOf('EUR'))->toBe('currency')
        ->and(pqOf('MVA'))->toBe('power')          // three capitals, but not a currency
        ->and(pqOf('kg CO₂ eq'))->toBe('global warming potential')
        ->and(pqOf('°'))->toBe('plane angle');

    expect(DB::table('physical_quantities')->count())->toBeLessThan(200)
        ->and(DB::table('physical_quantities')->where('name', 'length')->value('namePt'))->toBe('comprimento');
});

test('every quantity name used by the classifier has a Portuguese label', function () {
    $used = array_unique(array_merge(array_values(QuantityKinds::BY_DIMENSION), array_values(QuantityKinds::BY_UNIT), ['currency', 'without']));
    expect(array_diff($used, array_keys(QuantityKinds::NAMES)))->toBe([]);
});

test('the seed is idempotent and leaves no corrupted unit symbols', function () {
    $before = DB::table('units')->orderBy('guid')->get()->toJson();
    $this->artisan('units:seed-reference', ['--apply' => true])->assertSuccessful();
    expect(DB::table('units')->orderBy('guid')->get()->toJson())->toBe($before);

    $codes = DB::table('units')->pluck('code');
    expect($codes->filter(fn ($c) => preg_match('/Â|â|Ã/u', $c))->all())->toBe([])
        ->and($codes)->toContain('mm⁴', 'kg O₃ eq', 'cmH₂O', 'MPa·√m');
});

test('dimension parser fixes: point is a length and J/kg·K divides by kelvin', function () {
    expect(SiDimension::canonical(SiDimension::derive('pt')))->toBe('L1')
        ->and(SiDimension::canonical(SiDimension::derive('J/kg·K')))->toBe('L2T-2Θ-1')
        ->and(SiDimension::canonical(SiDimension::derive('kg/m/s/Pa')))->toBe('T1')
        ->and(SiDimension::canonical(SiDimension::derive('mm/(m·K)')))->toBe('Θ-1');
});

test('hand-typed unit spellings resolve to the reference unit', function () {
    expect(UnitsReference::resolveCode('m2'))->toBe('m²')
        ->and(UnitsReference::resolveCode('N/mm2'))->toBe('N/mm²')
        ->and(UnitsReference::resolveCode('kg/m3'))->toBe('kg/m³')
        ->and(UnitsReference::resolveCode(' m3/h '))->toBe('m³/h')
        ->and(UnitsReference::resolveCode('KN'))->toBe('kN')
        ->and(UnitsReference::resolveCode('Eur'))->toBe('EUR')
        ->and(UnitsReference::resolveCode('W/mK'))->toBe('W/(m·K)')
        ->and(UnitsReference::resolveCode('W/m²K'))->toBe('W/(m²·K)')
        ->and(UnitsReference::resolveCode('kg CO2 eq'))->toBe('kg CO₂ eq')
        // case matters when it tells two units apart
        ->and(UnitsReference::resolveCode('MW'))->toBe('MW')
        ->and(UnitsReference::resolveCode('mW'))->toBe('mW')
        ->and(UnitsReference::resolveCode('furlongs'))->toBeNull()
        ->and(UnitsReference::resolveCode(''))->toBeNull();
});

test('unit, quantity and dimension pages link to each other', function () {
    $this->get(UriService::link('unit', 'N/mm²'))->assertOk()
        ->assertSee(UriService::link('pq', 'pressure'), false)
        ->assertSee(UriService::link('dim', 'L-1M1T-2'), false)
        ->assertSee(UriService::link('unit', 'MPa'), false);     // other units of pressure

    $this->get(UriService::link('pq', 'pressure'))->assertOk()
        ->assertSee('pressão')->assertSee(UriService::link('unit', 'kPa'), false);

    $this->get(UriService::link('dim', 'L-1M1T-2'))->assertOk()
        ->assertSee(UriService::link('pq', 'pressure'), false)->assertSee(UriService::link('unit', 'bar'), false);

    $this->get(route('reference.index'))->assertOk()->assertSee(UriService::link('pq', 'length'), false);
    $this->get(route('reference.index', ['q' => 'm2']))->assertOk()->assertSee(UriService::link('unit', 'm²'), false);
});

test('unit page lists the dictionary properties measured in it, whatever the spelling', function () {
    F::base();
    $p = F::dict(['nameEn' => 'Compressive strength', 'namePt' => 'Resistência à compressão', 'namePtSc' => 'Resistência à compressão', 'units' => 'N/mm2', 'status' => 'Active']);

    $this->get(UriService::link('unit', 'N/mm²'))->assertOk()
        ->assertSee('Resistência à compressão')->assertSee(UriService::buildLink('prop', $p), false);
});

test('other spellings and old identifiers redirect to the canonical page', function () {
    $this->get(UriService::link('unit', 'm2'))->assertStatus(301)->assertRedirect(UriService::link('unit', 'm²'));
    $this->get(UriService::link('unit', 'KN'))->assertStatus(301)->assertRedirect(UriService::link('unit', 'kN'));
    // quantity kinds used to be named after a unit; exported links keep working
    $this->get(UriService::link('pq', 'millimetre'))->assertStatus(301)->assertRedirect(UriService::link('pq', 'length'));
    $this->get(UriService::link('pq', 'comprimento'))->assertStatus(301)->assertRedirect(UriService::link('pq', 'length'));
    $this->get(UriService::link('dim', 'L'))->assertStatus(301)->assertRedirect(UriService::link('dim', 'L1'));
    $this->get(UriService::link('unit', 'furlongs'))->assertNotFound();
});

test('property page links unit, quantity and dimension derived from the unit', function () {
    F::base();
    $p = F::dict(['units' => 'N/mm2', 'physicalQuantity' => 'Neuton per square millimeter | en.EN', 'dimension' => '', 'status' => 'Active']);

    $this->get(UriService::buildLink('prop', $p))->assertOk()
        ->assertSee(UriService::link('unit', 'N/mm²'), false)
        ->assertSee(UriService::link('pq', 'pressure'), false)
        ->assertSee(UriService::link('dim', 'L-1M1T-2'), false);
});

test('JSON for units carries the quantity and the dimension', function () {
    $this->getJson(UriService::link('unit', 'mm') . '?format=json')->assertOk()
        ->assertJsonPath('physicalQuantity.name', 'length')
        ->assertJsonPath('physicalQuantity.namePt', 'comprimento')
        ->assertJsonPath('dimension.canonical', 'L1');
    // a unit with a dimension but no named quantity keeps its dimension
    $this->getJson(UriService::link('unit', 'A/J') . '?format=json')->assertOk()
        ->assertJsonPath('physicalQuantity', null)
        ->assertJsonPath('dimension.canonical', 'L-2M-1T2I1');
});

test('the ISO 23387 export carries unit, quantity and dimension for a hand-typed unit', function () {
    $tpl = F::pdt([1]);
    DB::table('propertiesdatadictionaries')->whereIn('Id', collect($tpl['dicts'])->pluck('Id'))->update(['units' => 'm2']);

    $json = json_encode($this->get('/api/' . $tpl['pdt']->Id . '/json')->assertOk()->json(), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    $unitGuid = DB::table('units')->whereRaw('BINARY code = ?', ['m²'])->value('guid');
    expect($json)->toContain($unitGuid)
        ->and($json)->toContain('area | en.EN')
        ->and($json)->toContain(UnitsReference::dimensionUri('L2'));
});
