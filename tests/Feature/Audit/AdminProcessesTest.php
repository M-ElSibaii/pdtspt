<?php

use App\Services\DraftVersionService;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Tests\Support\PdtFixtures as F;

/*
 * Admin processes (b)-(g), driven over HTTP as the admin pages drive them. Refusal for
 * guests and non-admins on every one of these routes is proven by AdminAccessTest; here
 * each process is proven to write the right rows, and to refuse what it must refuse.
 */

beforeEach(function () {
    Http::preventStrayRequests();
    Mail::fake();
    F::base(); // the 'n/a' reference document every real database carries
    $this->actingAs(F::admin());
});

function audit_pdt(int $id): ?object
{
    return DB::table('productdatatemplates')->where('Id', $id)->first();
}

// ---------------------------------------------------------------- (e) create a PDT

test('creating a PDT from an existing construction object writes one Preview draft', function () {
    $co = F::constructionObject();
    $before = DB::table('productdatatemplates')->count();

    $res = $this->post(route('admin.pdt.create.store'), [
        'co_mode' => 'existing', 'constructionObjectGUID' => $co,
        'pdt' => ['pdtNameEn' => 'Roof tile', 'pdtNamePt' => 'Telha', 'GUID' => 'ffffffffffffffffffffffffffffffff', 'status' => 'Active'],
    ]);

    $row = DB::table('productdatatemplates')->where('pdtNameEn', 'Roof tile')->first();
    $res->assertRedirect(route('admin.previews.editor', ['pdt' => $row->Id]));
    expect(DB::table('productdatatemplates')->count())->toBe($before + 1);
    // System fields are never taken from the form.
    expect($row->status)->toBe('Preview')->and($row->GUID)->not->toBe('ffffffffffffffffffffffffffffffff')
        ->and([(int) $row->versionNumber, (int) $row->revisionNumber])->toBe([1, 0])
        ->and($row->constructionObjectGUID)->toBe($co);
    // Rule as found (Lineage::abortIfHiddenDraft): a brand-new template's draft keeps the
    // public visibility it always had; only a new-version draft of a published lineage is
    // hidden. Listed under Decisions.
    auth()->logout();
    $this->getJson('/api/' . $row->Id)->assertOk();
});

test('creating a PDT with a new construction object writes both as Preview', function () {
    $this->post(route('admin.pdt.create.store'), [
        'co_mode' => 'new',
        'co' => ['constructionObjectNameEn' => 'Tile', 'constructionObjectNamePt' => 'Telha'],
        'pdt' => ['pdtNameEn' => 'Ceramic tile', 'pdtNamePt' => 'Telha cerâmica'],
    ])->assertRedirect();
    $pdt = DB::table('productdatatemplates')->where('pdtNameEn', 'Ceramic tile')->first();
    $co = DB::table('constructionobjects')->where('GUID', $pdt->constructionObjectGUID)->first();
    expect($co->constructionObjectNameEn)->toBe('Tile')->and($co->status)->toBe('Preview');
});

test('creating a PDT without its mandatory fields writes nothing and explains why', function () {
    $before = DB::table('productdatatemplates')->count();
    $this->from(route('admin.pdt.create'))->post(route('admin.pdt.create.store'), ['co_mode' => 'existing', 'pdt' => []])
        ->assertRedirect(route('admin.pdt.create'))->assertSessionHas('createErrors');
    expect(DB::table('productdatatemplates')->count())->toBe($before);
});

// ---------------------------------------------------------------- (b)(d) groups and properties in a draft

test('a draft is authored over HTTP: template, group, new and existing properties, removal', function () {
    $draft = F::pdt([1], ['status' => 'Preview', 'versionNumber' => 1, 'revisionNumber' => 0]);
    $id = $draft['pdt']->Id;
    $shared = F::dict(['nameEn' => 'Length', 'namePt' => 'Comprimento']);

    $this->postJson(route('admin.previews.editPdt', ['pdt' => $id]), ['attrs' => ['descriptionEn' => 'edited', 'GUID' => 'x', 'status' => 'Active']])
        ->assertOk()->assertJson(['ok' => true]);
    expect(audit_pdt($id)->descriptionEn)->toBe('edited')->and(audit_pdt($id)->status)->toBe('Preview')
        ->and(audit_pdt($id)->GUID)->toBe($draft['pdt']->GUID);

    $gopId = $this->postJson(route('admin.previews.addGop', ['pdt' => $id]), ['attrs' => ['gopNameEn' => 'Acoustics', 'gopNamePt' => 'Acústica']])
        ->assertOk()->json('gopId');
    $gop = DB::table('groupofproperties')->where('Id', $gopId)->first();
    expect($gop->pdtId)->toBe($id)->and($gop->status)->toBe('Preview')->and(strlen($gop->GUID))->toBe(32);

    $this->postJson(route('admin.previews.editGop', ['pdt' => $id]), ['gopId' => $gopId, 'attrs' => ['definitionEn' => 'sound']])->assertOk();
    expect(DB::table('groupofproperties')->where('Id', $gopId)->value('definitionEn'))->toBe('sound');

    $new = $this->postJson(route('admin.previews.addNew', ['pdt' => $id]), ['gopId' => $gopId, 'values' => [
        'nameEn' => 'SoundReductionIndex', 'namePt' => 'IndiceDeReducaoSonora', 'nameEnSc' => 'Sound reduction index',
        'namePtSc' => 'Índice de redução sonora', 'definitionEn' => 'Rw', 'definitionPt' => 'Rw', 'dataType' => 'Real', 'units' => 'dB',
    ]])->assertOk()->json();
    $dict = DB::table('propertiesdatadictionaries')->where('Id', $new['dictId'])->first();
    expect($dict->status)->toBe('Preview')->and([(int) $dict->versionNumber, (int) $dict->revisionNumber])->toBe([1, 0])
        ->and($dict->GUID)->toBe($new['guid'])->and($dict->units)->toBe('dB');

    $ctxId = $this->postJson(route('admin.previews.addExisting', ['pdt' => $id]), ['gopId' => $gopId, 'dictId' => $shared->Id])
        ->assertOk()->json('contextId');
    expect(DB::table('properties')->where('Id', $ctxId)->value('propertyId'))->toBe($shared->Id);

    $this->postJson(route('admin.previews.removeContext', ['pdt' => $id]), ['contextId' => $ctxId])->assertOk();
    expect(DB::table('properties')->where('Id', $ctxId)->exists())->toBeFalse();
    expect(DB::table('propertiesdatadictionaries')->where('Id', $shared->Id)->exists())->toBeTrue();

    $this->postJson(route('admin.previews.removeGop', ['pdt' => $id]), ['gopId' => $gopId])->assertOk();
    expect(DB::table('groupofproperties')->where('Id', $gopId)->exists())->toBeFalse();
    expect(DB::table('properties')->where('gopID', $gopId)->count())->toBe(0);
});

test('a new property with a non-bSDD data type or unit, or no data type, is refused with 422', function () {
    $draft = F::pdt([1], ['status' => 'Preview', 'versionNumber' => 1, 'revisionNumber' => 0]);
    $gopId = $draft['gops'][0]->Id;
    $base = ['nameEn' => 'X', 'namePt' => 'X', 'nameEnSc' => 'x', 'namePtSc' => 'x', 'definitionEn' => 'x', 'definitionPt' => 'x'];
    $before = DB::table('propertiesdatadictionaries')->count();

    foreach ([$base, $base + ['dataType' => 'Float'], $base + ['dataType' => 'Real', 'units' => 'furlongs']] as $values) {
        $this->postJson(route('admin.previews.addNew', ['pdt' => $draft['pdt']->Id]), ['gopId' => $gopId, 'values' => $values])
            ->assertStatus(422)->assertJson(['ok' => false]);
    }
    expect(DB::table('propertiesdatadictionaries')->count())->toBe($before);
});

test('the draft editor refuses to touch a live template or its rows', function () {
    $live = F::pdt([1]);
    $draft = F::pdt([1], ['status' => 'Preview', 'versionNumber' => 1, 'revisionNumber' => 0]);
    $gop = $live['gops'][0];
    $ctx = collect($live['ctx'])->flatten()->first();
    $snapshot = fn() => [audit_pdt($live['pdt']->Id), DB::table('groupofproperties')->where('pdtId', $live['pdt']->Id)->get(),
        DB::table('properties')->where('pdtID', $live['pdt']->Id)->get(), DB::table('propertiesdatadictionaries')->get()];
    $before = $snapshot();

    // Directly, and through a draft's URL with a live row's id in the body.
    $this->postJson(route('admin.previews.editPdt', ['pdt' => $live['pdt']->Id]), ['attrs' => ['pdtNameEn' => 'renamed']])->assertStatus(422);
    $this->postJson(route('admin.previews.editGop', ['pdt' => $draft['pdt']->Id]), ['gopId' => $gop->Id, 'attrs' => ['gopNameEn' => 'renamed']])->assertStatus(422);
    $this->postJson(route('admin.previews.editContext', ['pdt' => $draft['pdt']->Id]), ['contextId' => $ctx->Id, 'attrs' => ['descriptionEn' => 'x']])->assertStatus(422);
    $this->postJson(route('admin.previews.editProperty', ['pdt' => $draft['pdt']->Id]), ['contextId' => $ctx->Id, 'values' => ['definitionEn' => 'x']])->assertStatus(422);
    $this->postJson(route('admin.previews.removeContext', ['pdt' => $draft['pdt']->Id]), ['contextId' => $ctx->Id])->assertStatus(422);
    $this->postJson(route('admin.previews.removeGop', ['pdt' => $draft['pdt']->Id]), ['gopId' => $gop->Id])->assertStatus(422);
    $this->postJson(route('admin.previews.addGop', ['pdt' => $live['pdt']->Id]), ['attrs' => ['gopNameEn' => 'a', 'gopNamePt' => 'a']])->assertStatus(422);
    $this->postJson(route('admin.previews.deleteApply', ['pdt' => $live['pdt']->Id]))->assertStatus(422);

    expect($snapshot())->toEqual($before);
});

// ---------------------------------------------------------------- (c) versions and revisions

test('a published new version keeps the GUID, and the earlier version stays readable', function () {
    $live = F::pdt([1], ['pdtNameEn' => 'Brick', 'pdtNamePt' => 'Tijolo']);
    $oldId = $live['pdt']->Id;
    $this->post(route('admin.pdt.newVersion.create', ['pdt' => $oldId]))->assertRedirect();
    $draftId = DB::table('productdatatemplates')->where('GUID', $live['pdt']->GUID)->where('status', 'Preview')->value('Id');
    $this->postJson(route('admin.previews.addGop', ['pdt' => $draftId]), ['attrs' => ['gopNameEn' => 'Fire', 'gopNamePt' => 'Fogo']])->assertOk();

    $this->postJson(route('admin.previews.publishApply', ['pdt' => $draftId]))->assertOk()->assertJson(['ok' => true]);

    $new = audit_pdt($draftId);
    $old = audit_pdt($oldId);
    expect($new->GUID)->toBe($old->GUID)
        ->and([(int) $new->versionNumber, (int) $new->revisionNumber])->toBe([3, 0])
        ->and($new->status)->toBe('Active')->and($old->status)->toBe('InActive')
        // Rule as found: a new version is dated the day it is published; the old row keeps its dates.
        ->and(substr((string) $new->dateOfVersion, 0, 10))->toBe(Carbon::today()->toDateString())
        ->and(substr((string) $old->dateOfVersion, 0, 10))->toBe('2024-01-01');

    // The earlier version is still readable: by Id over the API and by its pinned URI.
    $this->getJson("/api/{$oldId}")->assertOk();
    $this->getJson("/api/{$oldId}/json")->assertOk();
    $this->getJson('/uri/0.1/dt/Tijolo/v2')->assertOk()->assertJsonPath('record.Id', $oldId);
    // The unpinned URI is the current version.
    $this->getJson('/uri/0.1/dt/Tijolo')->assertOk()->assertJsonPath('record.Id', $draftId);
});

test('a property edited in a draft becomes a new dictionary row with the same GUID; the old one stays', function () {
    $live = F::pdt([1]);
    $orig = $live['dicts'][0];
    $this->post(route('admin.pdt.newVersion.create', ['pdt' => $live['pdt']->Id]));
    $draftId = DB::table('productdatatemplates')->where('GUID', $live['pdt']->GUID)->where('status', 'Preview')->value('Id');
    $ctx = DB::table('properties')->where('pdtID', $draftId)->first();

    $fork = $this->postJson(route('admin.previews.editProperty', ['pdt' => $draftId]), ['contextId' => $ctx->Id, 'values' => ['definitionEn' => 'Sharper']])
        ->assertOk()->json();
    expect($fork['forked'])->toBeTrue();
    $this->postJson(route('admin.previews.publishApply', ['pdt' => $draftId]), ['decisions' => [$fork['contextId'] => 'version']])->assertOk();

    $rows = DB::table('propertiesdatadictionaries')->where('GUID', $orig->GUID)->orderBy('Id')->get();
    expect($rows)->toHaveCount(2);
    expect($rows[0]->Id)->toBe($orig->Id)->and($rows[0]->definitionEn)->toBe($orig->definitionEn);
    expect($rows[1]->definitionEn)->toBe('Sharper')->and((int) $rows[1]->versionNumber)->toBe(2)->and($rows[1]->status)->toBe('Active');
    $this->getJson('/api/dataDictionary/' . $orig->Id)->assertOk()->assertJsonPath('definitionEn', $orig->definitionEn);
});

// ---------------------------------------------------------------- (f) deletion, live edits

test('rule as found: a publish decision other than "version" keeps the live definition and drops the edit', function () {
    $live = F::pdt([1]);
    $orig = $live['dicts'][0];
    $this->post(route('admin.pdt.newVersion.create', ['pdt' => $live['pdt']->Id]));
    $draftId = DB::table('productdatatemplates')->where('GUID', $live['pdt']->GUID)->where('status', 'Preview')->value('Id');
    $ctx = DB::table('properties')->where('pdtID', $draftId)->first();
    $fork = $this->postJson(route('admin.previews.editProperty', ['pdt' => $draftId]), ['contextId' => $ctx->Id, 'values' => ['definitionEn' => 'Sharper']])->json();
    $this->postJson(route('admin.previews.editContext', ['pdt' => $draftId]), ['contextId' => $ctx->Id, 'attrs' => ['descriptionEn' => 'usage']]);

    $this->postJson(route('admin.previews.publishApply', ['pdt' => $draftId]), ['decisions' => [$ctx->Id => 'new_version']])->assertOk();

    expect(DB::table('propertiesdatadictionaries')->where('Id', $fork['dictId'])->exists())->toBeFalse();
    expect(DB::table('propertiesdatadictionaries')->where('GUID', $orig->GUID)->pluck('definitionEn')->all())->toBe([$orig->definitionEn]);
});

test('deleting a brand-new draft is planned first, then removes only its own rows', function () {
    $keep = F::dict();
    $draft = F::pdt([2], ['status' => 'Preview', 'versionNumber' => 1, 'revisionNumber' => 0]);
    $id = $draft['pdt']->Id;
    DB::table('properties')->insert(['GUID' => $keep->GUID, 'propertyId' => $keep->Id, 'gopID' => $draft['gops'][0]->Id,
        'pdtID' => $id, 'descriptionEn' => '', 'descriptionPt' => '', 'referenceDocumentGUID' => 'n/a']);
    $other = F::pdt([1]);
    DB::table('properties')->insert(['GUID' => $keep->GUID, 'propertyId' => $keep->Id, 'gopID' => $other['gops'][0]->Id,
        'pdtID' => $other['pdt']->Id, 'descriptionEn' => '', 'descriptionPt' => '', 'referenceDocumentGUID' => 'n/a']);
    $count = DB::table('productdatatemplates')->count();

    $this->getJson(route('admin.previews.deletePlan', ['pdt' => $id]))->assertOk()->assertJson(['ok' => true]);
    expect(DB::table('productdatatemplates')->count())->toBe($count);

    $this->postJson(route('admin.previews.deleteApply', ['pdt' => $id]))->assertOk();
    expect(audit_pdt($id))->toBeNull()
        ->and(DB::table('groupofproperties')->where('pdtId', $id)->count())->toBe(0)
        ->and(DB::table('properties')->where('pdtID', $id)->count())->toBe(0)
        ->and(DB::table('propertiesdatadictionaries')->where('Id', $keep->Id)->exists())->toBeTrue()
        ->and(audit_pdt($other['pdt']->Id)->status)->toBe('Active');
});

test('the live editor edits only usage text and mappings, in place, on Active templates', function () {
    $live = F::pdt([1]);
    $ctx = collect($live['ctx'])->flatten()->first();
    $url = route('admin.pdt.active.context', ['pdt' => $live['pdt']->Id]);

    $this->postJson($url, ['contextId' => $ctx->Id, 'attrs' => ['descriptionEn' => 'clarified', 'propertyId' => 1, 'GUID' => 'x']])
        ->assertOk()->assertJson(['saved' => ['descriptionEn']]);
    $row = DB::table('properties')->where('Id', $ctx->Id)->first();
    expect($row->descriptionEn)->toBe('clarified')->and($row->GUID)->toBe($ctx->GUID)->and($row->propertyId)->toBe($ctx->propertyId);

    $this->postJson(route('admin.pdt.active.mapping', ['pdt' => $live['pdt']->Id]),
        ['contextId' => $ctx->Id, 'attrs' => ['relationToOtherDataDictionaries' => 'https://example.org/p', 'definitionEn' => 'changed']])
        ->assertOk()->assertJson(['saved' => ['relationToOtherDataDictionaries']]);
    expect(DB::table('propertiesdatadictionaries')->where('Id', $ctx->propertyId)->value('definitionEn'))->toBe($live['dicts'][0]->definitionEn);

    // A context row of another template cannot be edited through this one.
    $other = F::pdt([1]);
    $otherCtx = collect($other['ctx'])->flatten()->first();
    $this->postJson($url, ['contextId' => $otherCtx->Id, 'attrs' => ['descriptionEn' => 'x']])->assertStatus(422);
    // Drafts go through the draft editor, not this one.
    $draft = F::pdt([1], ['status' => 'Preview']);
    $this->get(route('admin.pdt.activeEdit', ['pdt' => $draft['pdt']->Id]))->assertRedirect(route('dashboard'));
    $this->postJson(route('admin.pdt.active.context', ['pdt' => $draft['pdt']->Id]),
        ['contextId' => collect($draft['ctx'])->flatten()->first()->Id, 'attrs' => ['descriptionEn' => 'x']])->assertStatus(422);
});

// ---------------------------------------------------------------- reference documents, relations

test('reference documents: the AJAX create writes one row with a fresh GUID', function () {
    $res = $this->postJson(route('admin.refdoc.createAjax'), ['name' => 'EN 771-1', 'title' => 'Clay masonry units', 'description' => 'd'])
        ->assertOk()->assertJson(['ok' => true]);
    $row = DB::table('referencedocuments')->where('GUID', $res->json('guid'))->first();
    expect($row->rdName)->toBe('EN 771-1')->and($row->status)->toBe('Current');
    $this->postJson(route('admin.refdoc.createAjax'), ['name' => 'x'])->assertStatus(422);
});

test('reference documents: the list form refuses a GUID that already exists instead of failing with 500', function () {
    $existing = F::guid();
    DB::table('referencedocuments')->insert(['GUID' => $existing, 'rdName' => 'Original', 'title' => 't', 'description' => 'd', 'status' => 'Current']);

    $res = $this->post(route('referencedocuments.create'), ['GUID' => $existing, 'name' => 'Other', 'title' => 't', 'description' => 'd', 'status' => 'Current']);

    $res->assertSessionHasErrors('GUID');
    expect(DB::table('referencedocuments')->where('GUID', $existing)->value('rdName'))->toBe('Original');
});

test('relations between templates are created and removed (soft) by an admin', function () {
    $a = F::pdt([1]);
    $b = F::pdt([1]);
    $res = $this->postJson(route('admin.relations.store'), ['sourceEntityType' => 'pdt', 'sourceGuid' => $a['pdt']->GUID,
        'relationType' => 'IsSubtypeOf', 'targetEntityType' => 'pdt', 'targetGuid' => $b['pdt']->GUID])->assertOk();
    $id = $res->json('relation.id');
    expect(DB::table('entity_relationships')->where('id', $id)->whereNull('deleted_at')->exists())->toBeTrue();

    // A template cannot be its own subtype.
    $this->postJson(route('admin.relations.store'), ['sourceEntityType' => 'pdt', 'sourceGuid' => $a['pdt']->GUID,
        'relationType' => 'IsSubtypeOf', 'targetEntityType' => 'pdt', 'targetGuid' => $a['pdt']->GUID])->assertStatus(422);

    $this->deleteJson(route('admin.relations.destroy', ['id' => $id]))->assertOk();
    expect(DB::table('entity_relationships')->where('id', $id)->whereNotNull('deleted_at')->exists())->toBeTrue();
});

test('property dependencies are created and removed by an admin', function () {
    $src = F::dict();
    $tgt = F::dict();
    $id = $this->postJson(route('admin.propdeps.store'), ['sourcePropertyGuid' => $src->GUID, 'dependencyKind' => 'function',
        'expression' => 'x*2', 'targets' => [['guid' => $tgt->GUID]]])->assertOk()->json('id');
    expect(DB::table('property_dependencies')->where('id', $id)->exists())->toBeTrue();
    $this->postJson(route('admin.propdeps.store'), ['sourcePropertyGuid' => $src->GUID, 'dependencyKind' => 'function'])->assertStatus(422);
    $this->deleteJson(route('admin.propdeps.destroy', ['id' => $id]))->assertOk();
});

// ---------------------------------------------------------------- uploads, dedupe, status check

test('the property picker upload accepts spreadsheets only, within the size limit', function () {
    $this->postJson(route('admin.picker.match'), ['excelFile' => UploadedFile::fake()->create('evil.php', 10, 'application/x-php')])
        ->assertStatus(422)->assertJsonValidationErrors('excelFile');
    $this->postJson(route('admin.picker.match'), ['excelFile' => UploadedFile::fake()->create('big.xlsx', 5000,
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')])
        ->assertStatus(422)->assertJsonValidationErrors('excelFile');
    $this->postJson(route('admin.picker.match'), [])->assertStatus(422);
});

test('dedupe pages render, and invalid decisions answer 422 without writing', function () {
    F::pdt([1]);
    $this->get(route('admin.dedupe'))->assertOk();
    $this->get(route('admin.dedupeRefDocs'))->assertOk();
    $before = DB::table('propertiesdatadictionaries')->get();
    $this->postJson(route('admin.dedupe.apply'), ['decision' => ['key' => 'nothing-like-this']])->assertStatus(422);
    $this->postJson(route('admin.dedupeRefDocs.apply'), ['decision' => ['key' => 'nothing-like-this']])->assertStatus(422);
    $this->postJson(route('admin.dedupe.dict'), ['dictId' => 999999, 'definitionEn' => 'x'])->assertStatus(422);
    $this->postJson(route('admin.dedupe.reviewState'), ['dictId' => 'abc'])->assertStatus(422);
    expect(DB::table('propertiesdatadictionaries')->get())->toEqual($before);
});

test('rule as found: the dedupe definition editor rewrites an Active definition in place, with no new version', function () {
    $live = F::pdt([1]);
    $d = $live['dicts'][0];
    $this->postJson(route('admin.dedupe.dict'), ['dictId' => $d->Id, 'definitionEn' => 'Rewritten', 'definitionPt' => $d->definitionPt])->assertOk();
    $rows = DB::table('propertiesdatadictionaries')->where('GUID', $d->GUID)->get();
    expect($rows)->toHaveCount(1);
    expect($rows[0]->definitionEn)->toBe('Rewritten')
        ->and([(int) $rows[0]->versionNumber, (int) $rows[0]->revisionNumber])->toBe([1, 0])
        ->and($rows[0]->dateOfRevision)->toStartWith('2024-01-01');
});

test('the status check with nothing selected changes nothing', function () {
    F::pdt([1]);
    $before = DB::table('propertiesdatadictionaries')->get();
    $this->post(route('admin.statusCheck.apply'), [])->assertRedirect(route('admin.statusCheck'))->assertSessionHas('error');
    expect(DB::table('propertiesdatadictionaries')->get())->toEqual($before);
});
