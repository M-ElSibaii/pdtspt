<?php

use App\Services\StatusHealthService;
use App\Services\VersioningService;
use Illuminate\Support\Facades\DB;
use Tests\Support\PdtFixtures as F;

beforeEach(function () {
    $this->backupsBefore = glob(storage_path('app/*_backup_*.json')) ?: [];
});

afterEach(function () {
    foreach (array_diff(glob(storage_path('app/*_backup_*.json')) ?: [], $this->backupsBefore) as $f) {
        @unlink($f);
    }
});

test('a consistent database reports nothing to fix', function () {
    F::pdt([2, 1]);

    $report = app(StatusHealthService::class)->report();
    expect($report['total'])->toBe(0)->and($report['issues'])->toBe([]);
});

test('the dry run projects template changes onto their groups and properties', function () {
    // Two Active versions of one lineage: the older must retire, and in the SAME dry run
    // its groups and its exclusive properties must be reported as retiring too.
    $old = F::pdt([1], ['versionNumber' => 1, 'revisionNumber' => 0]);
    F::pdt([1], ['GUID' => $old['pdt']->GUID]);

    $changes = collect(app(VersioningService::class)->recomputeStatuses(false));

    expect($changes->where('table', 'productdatatemplates')->pluck('id')->all())->toBe([$old['pdt']->Id]);
    expect($changes->where('table', 'groupofproperties')->pluck('id')->all())->toBe([$old['gops'][0]->Id]);
    expect($changes->where('table', 'propertiesdatadictionaries')->pluck('id')->all())->toBe([$old['dicts'][0]->Id]);
});

test('the report explains each fix and flags lineages with two Active versions', function () {
    $old = F::pdt([1], ['versionNumber' => 1, 'revisionNumber' => 0]);
    F::pdt([1], ['GUID' => $old['pdt']->GUID]);

    $report = app(StatusHealthService::class)->report();
    $pdtFix = $report['byTable']['productdatatemplates'][0];
    expect($pdtFix['to'])->toBe('InActive')->and($pdtFix['reason'])->toBe('A newer published version exists')
        ->and($pdtFix['key'])->toBe('productdatatemplates:' . $old['pdt']->Id);
    expect(collect($report['issues'])->pluck('severity')->all())->toContain('error');
});

test('only the selected fixes are applied, with a backup', function () {
    $old = F::pdt([1], ['versionNumber' => 1, 'revisionNumber' => 0]);
    F::pdt([1], ['GUID' => $old['pdt']->GUID]);
    $this->actingAs(F::admin());
    $pending = app(StatusHealthService::class)->report()['total'];

    $this->post(route('admin.statusCheck.apply'), ['keys' => ['productdatatemplates:' . $old['pdt']->Id]])
        ->assertRedirect(route('admin.statusCheck'))->assertSessionHas('success');

    expect(DB::table('productdatatemplates')->where('Id', $old['pdt']->Id)->value('status'))->toBe('InActive');
    // Not selected -> untouched (still pending on the next check).
    expect(DB::table('groupofproperties')->where('Id', $old['gops'][0]->Id)->value('status'))->toBe('Active');
    expect(app(StatusHealthService::class)->report()['total'])->toBe($pending - 1);
    expect(array_diff(glob(storage_path('app/status_check_backup_*.json')) ?: [], $this->backupsBefore))->not->toBeEmpty();
});

test('applying with nothing selected changes nothing', function () {
    $old = F::pdt([1], ['versionNumber' => 1, 'revisionNumber' => 0]);
    F::pdt([1], ['GUID' => $old['pdt']->GUID]);
    $this->actingAs(F::admin());

    $this->post(route('admin.statusCheck.apply'), [])->assertSessionHas('error');
    expect(DB::table('productdatatemplates')->where('Id', $old['pdt']->Id)->value('status'))->toBe('Active');
});

test('properties used only by drafts are reported as Preview, and draft rows used live as Active', function () {
    $draft = F::pdt([1], ['status' => 'Preview']);
    DB::table('propertiesdatadictionaries')->where('Id', $draft['dicts'][0]->Id)->update(['status' => 'Active']);
    $live = F::pdt([1]);
    DB::table('propertiesdatadictionaries')->where('Id', $live['dicts'][0]->Id)->update(['status' => 'Preview']);
    DB::table('constructionobjects')->where('GUID', $live['pdt']->constructionObjectGUID)->update(['status' => 'Preview']);

    $changes = collect(app(VersioningService::class)->recomputeStatuses(false))->keyBy(fn($c) => $c['table'] . ':' . $c['id']);

    expect($changes['propertiesdatadictionaries:' . $draft['dicts'][0]->Id]['to'])->toBe('Preview');
    expect($changes['propertiesdatadictionaries:' . $live['dicts'][0]->Id]['to'])->toBe('Active');
    expect($changes['constructionobjects:' . $live['pdt']->constructionObjectGUID]['to'])->toBe('Active');
});
