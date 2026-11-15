<?php

use App\Services\UriService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Tests\Support\PdtFixtures as F;

/*
 * Before a dedupe merge (dictionary or reference documents) is applied, the page lists what
 * will be permanently deleted (GUID, version, URI), says plainly that this cannot be undone,
 * and the admin must tick a box. The merge itself is unchanged (see AdminProcessesTest,
 * "rule as found: merging duplicates…").
 *
 * The tick is enforced in the page: Apply is disabled for a merge until the box is ticked,
 * and the click handler refuses without it. The server does not check it (see the report).
 */

beforeEach(function () {
    Http::preventStrayRequests();
    $this->actingAs(F::admin());
});

test('the dictionary merge confirmation lists each GUID, version and URI to be deleted and needs the tick', function () {
    $a = F::pdt([1]);
    $b = F::pdt([1]);
    DB::table('propertiesdatadictionaries')->where('Id', $a['dicts'][0]->Id)->update(['nameEn' => 'Colour', 'namePt' => 'Cor']);
    DB::table('propertiesdatadictionaries')->where('Id', $b['dicts'][0]->Id)
        ->update(['nameEn' => 'Colour', 'namePt' => 'Cor', 'versionNumber' => 3, 'revisionNumber' => 2]);
    $dup = DB::table('propertiesdatadictionaries')->where('Id', $b['dicts'][0]->Id)->first();
    $survivor = DB::table('propertiesdatadictionaries')->where('Id', $a['dicts'][0]->Id)->first();

    $html = $this->get(route('admin.dedupe'))->assertOk()->getContent();

    // The row to delete: its GUID, version and its own URI.
    expect($html)->toContain('Will be permanently deleted:')
        ->toContain($dup->GUID)
        ->toContain('v3.2')
        ->toContain(UriService::buildPinned('prop', $dup))
        ->toContain('These identifiers will no longer resolve to these rows, and this cannot be undone.');
    // The survivor (first row, the default choice) is listed but hidden: it is kept.
    expect($html)->toMatch('~<li class="dd-delete-row" data-id="' . $survivor->Id . '"\s+hidden\s*>~')
        ->and($html)->toMatch('~<li class="dd-delete-row" data-id="' . $dup->Id . '"\s*>~');

    // An unticked box, and the page refuses a merge without it.
    expect($html)->toMatch('~<input type="checkbox" class="dd-confirm-delete mt-1">~')
        ->and($html)->toContain("btn.disabled = action === 'merge' && !(confirmBox && confirmBox.checked)")
        ->and($html)->toContain('Tick the box to confirm the permanent deletion before merging.');

    // Rendering the page changes nothing.
    expect(DB::table('propertiesdatadictionaries')->where('GUID', $dup->GUID)->exists())->toBeTrue();
});

test('the reference-document merge confirmation lists each GUID and URI to be deleted and needs the tick', function () {
    F::base();
    foreach (['d0000000000000000000000000000011', 'd0000000000000000000000000000012'] as $guid) {
        DB::table('referencedocuments')->insert(['GUID' => $guid, 'rdName' => 'EN 771-1', 'title' => 'Masonry units',
            'status' => 'Active', 'created_at' => '2024-01-01', 'updated_at' => '2024-01-01']);
    }

    $html = $this->get(route('admin.dedupeRefDocs'))->assertOk()->getContent();

    expect($html)->toContain('A merge permanently deletes:')
        ->toContain('d0000000000000000000000000000012')
        ->toContain('version: not versioned')
        ->toContain('These identifiers will no longer resolve to these documents, and this cannot be undone.')
        ->toMatch('~<input type="checkbox" class="rd-confirm-delete mt-1">~')
        ->toContain("button.disabled = action === 'merge' && !(confirmBox && confirmBox.checked)")
        ->toContain('Tick the box to confirm the permanent deletion before merging.');
    $uri = UriService::build('doc', DB::table('referencedocuments')->where('GUID', 'd0000000000000000000000000000012')->first());
    expect($html)->toContain($uri);
});
