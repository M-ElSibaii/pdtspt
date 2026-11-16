<?php

use App\Services\PdtInheritanceService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\OriginFixture as O;
use Tests\Support\PdtFixtures as F;

/*
 * The property origin filter: every group a page lists carries its origin (this template,
 * an IsSubtypeOf ancestor, the master), each origin its property count and the swatch
 * colour already used for that source. Fixture: master <- parent ("Alvenaria") <- child.
 */

beforeEach(function () {
    Http::preventStrayRequests();
    O::build();
});

function originsByKey(array $filter): array
{
    return collect($filter['origins'])->keyBy('key')->all();
}

test('each group carries its origin and the counts are right', function () {
    $svc = new PdtInheritanceService();
    $child = DB::table('productdatatemplates')->where('Id', O::CHILD)->first();
    // Outside a request the app locale is config('app.locale'); a page request sets it from
    // the session (PT by default). Labels follow it.
    app()->setLocale('en');
    expect(array_column($svc->origins($child)['origins'], 'label'))->toBe(['This template', 'Masonry', 'Master template']);
    app()->setLocale('pt');
    $f = $svc->origins($child);

    expect($f['groupOrigin'])->toEqual([9103 => 'own', 9104 => 'own', 9102 => O::PARENT_GUID, 9101 => 'master']);
    expect(array_column($f['origins'], 'key'))->toBe(['own', O::PARENT_GUID, 'master']);
    expect(array_column($f['origins'], 'count'))->toBe([4, 2, 2]);
    expect(array_column($f['origins'], 'groupIds'))->toBe([[9103, 9104], [9102], [9101]]);
    expect(array_column($f['origins'], 'label'))->toBe(['Este modelo', 'Alvenaria', 'Modelo mestre']);
    expect($f['total'])->toBe(8);

    // Swatches are the colours the rows and legend already use.
    $colors = PdtInheritanceService::sourceColors(['Alvenaria', 'Modelo mestre']);
    expect(array_column($f['origins'], 'color'))->toBe(['#ffffff', $colors['Alvenaria'], $colors['Modelo mestre']]);

    // The parent inherits only from the master; the master has only its own groups.
    $parent = $svc->origins(DB::table('productdatatemplates')->where('Id', O::PARENT)->first());
    expect(array_column($parent['origins'], 'key'))->toBe(['own', 'master'])
        ->and(array_column($parent['origins'], 'count'))->toBe([2, 2]);
    $master = $svc->origins(DB::table('productdatatemplates')->where('Id', O::MASTER)->first());
    expect(array_column($master['origins'], 'key'))->toBe(['own'])
        ->and($master['total'])->toBe(2);
});

test('the public PDT page renders one checkbox per origin with its swatch and count, and tags every row', function () {
    $res = $this->get('/pdtsdownload/' . O::CHILD)->assertOk();
    $html = $res->getContent();
    $f = $res->viewData('originFilter');
    $o = originsByKey($f);

    expect($html)->toContain('x-data="originFilter(')
        ->toContain('aria-live="polite"')
        ->toContain('A mostrar 8 de 8 propriedades')
        ->toContain('Mostrar todas')
        ->toContain('As transferências (XLSX, XML, JSON) e a API incluem sempre todas as propriedades');
    foreach (['own' => 'Este modelo', O::PARENT_GUID => 'Alvenaria', 'master' => 'Modelo mestre'] as $key => $label) {
        expect($html)->toMatch('~<input type="checkbox" value="' . $key . '" x-model="selected" checked>\s*'
            . '<span class="origin-swatch" style="background: ' . preg_quote($o[$key]['color']) . ';" aria-hidden="true"></span>\s*'
            . '<span>' . $label . '</span>\s*<span class="origin-count">\(' . $o[$key]['count'] . '\)</span>~u');
    }

    // Every property row carries its origin; inherited rows keep their colour.
    expect(substr_count($html, '<tr data-origin="own"'))->toBe(4)
        ->and(substr_count($html, '<tr data-origin="' . O::PARENT_GUID . '"'))->toBe(2)
        ->and(substr_count($html, '<tr data-origin="master"'))->toBe(2);
    expect($html)->toContain('<tr data-origin="' . O::PARENT_GUID . '"  style="background-color: ' . $o[O::PARENT_GUID]['color'] . ';"');

    // "Dados gerais" holds the child's own group and the parent's: its heading lists both.
    expect($html)->toContain('data-origins="own ' . O::PARENT_GUID . '"')
        ->and($html)->toContain('data-origins="master"');

    // English labels come from the translations.
    $en = $this->get('/pdtsdownload/' . O::CHILD . '?lang=en')->assertOk()->getContent();
    expect($en)->toContain('<span>This template</span>')->toContain('<span>Masonry</span>')->toContain('<span>Master template</span>')
        ->toContain('Showing 8 of 8 properties')->toContain('Show all')
        ->toContain('Downloads (XLSX, XML, JSON) and the API always include every property, whatever the filter shows.');
});

test('the review page tags every row with its origin', function () {
    $this->actingAs(F::user());
    $html = $this->get('/pdtssurvey/' . O::CHILD)->assertOk()->getContent();

    expect($html)->toContain('x-data="originFilter(')
        ->and(substr_count($html, '<tr data-origin="own"'))->toBe(4)
        ->and(substr_count($html, '<tr data-origin="' . O::PARENT_GUID . '"'))->toBe(2)
        ->and(substr_count($html, '<tr data-origin="master"'))->toBe(2);
});

test('the quick-fix editor lists its own groups and the inherited ones read-only, each with its origin', function () {
    $this->actingAs(F::admin());
    $html = $this->get(route('admin.pdt.activeEdit', ['pdt' => O::CHILD]))->assertOk()->getContent();

    expect($html)->toContain('x-data="originFilter(')
        ->and(substr_count($html, 'data-origin="own"'))->toBe(2)
        ->and(substr_count($html, '<details class="adm-card" data-origin="' . O::PARENT_GUID . '"'))->toBe(1)
        ->and(substr_count($html, '<details class="adm-card" data-origin="master"'))->toBe(1)
        ->and($html)->toContain('TestProperty1 ')   // a master property, shown read-only
        ->and($html)->toContain('Grupos herdados (só leitura)');
});

test('the draft editor of a new version lists its own groups and the inherited ones, each with its origin', function () {
    $this->actingAs(F::admin());
    $this->post(route('admin.pdt.newVersion.create', ['pdt' => O::CHILD]))->assertRedirect();
    $draft = DB::table('productdatatemplates')->where('GUID', O::CHILD_GUID)->where('status', 'Preview')->first();
    expect($draft)->not->toBeNull();

    $res = $this->get(route('admin.previews.editor', ['pdt' => $draft->Id]))->assertOk();
    $html = $res->getContent();
    $f = $res->viewData('originFilter');

    expect(array_column($f['origins'], 'key'))->toBe(['own', O::PARENT_GUID, 'master'])
        ->and(array_column($f['origins'], 'count'))->toBe([4, 2, 2]);
    expect($html)->toContain('x-data="originFilter(')
        ->and(substr_count($html, 'data-origin="own"'))->toBe(2)
        ->and(substr_count($html, 'data-origin="' . O::PARENT_GUID . '"'))->toBe(1)
        ->and(substr_count($html, 'data-origin="master"'))->toBe(1);
});
