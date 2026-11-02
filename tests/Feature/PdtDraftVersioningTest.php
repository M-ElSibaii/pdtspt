<?php

use App\Services\DraftVersionService;
use App\Services\PreviewService;
use App\Services\UriService;
use Illuminate\Support\Facades\DB;
use Tests\Support\PdtFixtures as F;

/*
 * New versions of a published PDT are made through an offline draft: create -> edit
 * (any number of sessions) -> review the diff -> publish. These tests pin that flow and
 * the bugs that motivated it (a draft going live by accident, a template vanishing from
 * the dashboard after a version bump, admin routes open to any logged-in user).
 */

beforeEach(function () {
    $this->backupsBefore = glob(storage_path('app/*_backup_*.json')) ?: [];
});

afterEach(function () {
    foreach (array_diff(glob(storage_path('app/*_backup_*.json')) ?: [], $this->backupsBefore) as $f) {
        @unlink($f);
    }
});

function drafts(): DraftVersionService
{
    return app(DraftVersionService::class);
}

function pdtRow(int $id): object
{
    return DB::table('productdatatemplates')->where('Id', $id)->first();
}

function activeRows(string $guid)
{
    return DB::table('productdatatemplates')->where('GUID', $guid)->where('status', 'Active')->get();
}

// ============================================================ dashboard

test('dashboard keeps a template listed after a version bump resets the revision', function () {
    // v2.1 -> v3.0: the old query looked for MAX(version)=3 with MAX(revision)=1 and found nothing.
    $live = F::pdt([1], ['versionNumber' => 2, 'revisionNumber' => 1, 'status' => 'InActive', 'pdtNamePt' => 'Tijolo cerâmico']);
    $guid = $live['pdt']->GUID;
    F::pdt([1], ['GUID' => $guid, 'versionNumber' => 3, 'revisionNumber' => 0, 'pdtNamePt' => 'Tijolo cerâmico']);

    $this->get('/dashboard')->assertOk()->assertSee('Tijolo cerâmico')->assertSee('V3.0');
});

test('dashboard shows the live version while a draft is in progress', function () {
    $live = F::pdt([1], ['pdtNamePt' => 'Nome publicado']);
    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];
    DB::table('productdatatemplates')->where('Id', $draftId)->update(['pdtNamePt' => 'Nome rascunho']);

    $this->get('/dashboard')->assertOk()->assertSee('Nome publicado')->assertDontSee('Nome rascunho');
});

// ============================================================ security

test('admin routes reject logged-in non-admins', function () {
    $live = F::pdt([1]);
    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];
    DB::table('productdatatemplates')->where('Id', $draftId)->update(['descriptionEn' => 'changed']);

    $this->actingAs(F::user());
    $this->get('/admin')->assertRedirect('/');
    $this->get('/admin/status-check')->assertRedirect('/');
    $this->postJson(route('admin.previews.publishApply', ['pdt' => $draftId]))->assertRedirect('/');
    $this->post(route('admin.pdt.newVersion.create', ['pdt' => $live['pdt']->Id]))->assertRedirect('/');

    expect(pdtRow($draftId)->status)->toBe('Preview');
    expect(pdtRow($live['pdt']->Id)->status)->toBe('Active');
});

test('admin routes send guests to login', function () {
    $this->get('/admin')->assertRedirect('/login');
});

// ============================================================ start a draft

test('creating a new version makes an offline copy and leaves the live version untouched', function () {
    $live = F::pdt([2, 1]);
    $this->actingAs(F::admin());

    $res = $this->post(route('admin.pdt.newVersion.create', ['pdt' => $live['pdt']->Id]));
    $draft = DB::table('productdatatemplates')->where('GUID', $live['pdt']->GUID)->where('status', 'Preview')->first();
    $res->assertRedirect(route('admin.previews.editor', ['pdt' => $draft->Id]));

    expect($draft->versionNumber)->toBe(2)->and($draft->revisionNumber)->toBe(1);
    $draftGops = DB::table('groupofproperties')->where('pdtId', $draft->Id)->get();
    expect($draftGops->pluck('GUID')->all())->toBe(collect($live['gops'])->pluck('GUID')->all());
    expect($draftGops->pluck('status')->unique()->all())->toBe(['Preview']);
    expect(DB::table('properties')->where('pdtID', $draft->Id)->pluck('propertyId')->sort()->values()->all())
        ->toBe(collect($live['dicts'])->pluck('Id')->sort()->values()->all());

    // Live untouched.
    expect(pdtRow($live['pdt']->Id)->status)->toBe('Active');
    expect(DB::table('groupofproperties')->where('pdtId', $live['pdt']->Id)->where('status', 'Active')->count())->toBe(2);
    expect(activeRows($live['pdt']->GUID))->toHaveCount(1);
});

test('asking for a new version twice reuses the draft in progress', function () {
    $live = F::pdt([1]);
    $this->actingAs(F::admin());

    $this->post(route('admin.pdt.newVersion.create', ['pdt' => $live['pdt']->Id]));
    $this->post(route('admin.pdt.newVersion.create', ['pdt' => $live['pdt']->Id]));
    $drafts = DB::table('productdatatemplates')->where('GUID', $live['pdt']->GUID)->where('status', 'Preview')->get();
    expect($drafts)->toHaveCount(1);

    // The GET entry point jumps straight to it.
    $this->get(route('admin.pdt.newVersion', ['pdt' => $live['pdt']->Id]))
        ->assertRedirect(route('admin.previews.editor', ['pdt' => $drafts[0]->Id]));
});

test('a new version can only start from the current version', function () {
    $old = F::pdt([1], ['status' => 'InActive', 'versionNumber' => 1, 'revisionNumber' => 0]);
    F::pdt([1], ['GUID' => $old['pdt']->GUID]);

    expect(fn() => drafts()->startDraft($old['pdt']->Id))->toThrow(RuntimeException::class, 'not the current version');
});

// ============================================================ editing a draft

test('a group added to a draft can be filled with existing and new properties right away', function () {
    $live = F::pdt([1]);
    $other = F::dict();
    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];
    $this->actingAs(F::admin());

    $gopId = $this->postJson(route('admin.previews.addGop', ['pdt' => $draftId]), ['attrs' => ['gopNameEn' => 'Acoustics', 'gopNamePt' => 'Acústica']])
        ->assertOk()->json('gopId');
    $this->postJson(route('admin.previews.addExisting', ['pdt' => $draftId]), ['gopId' => $gopId, 'dictId' => $other->Id])
        ->assertOk()->assertJson(['ok' => true]);
    $new = $this->postJson(route('admin.previews.addNew', ['pdt' => $draftId]), ['gopId' => $gopId, 'values' => [
        'nameEn' => 'SoundReduction', 'namePt' => 'ReducaoSonora', 'nameEnSc' => 'Sound reduction', 'namePtSc' => 'Redução sonora',
        'definitionEn' => 'Rw', 'definitionPt' => 'Rw', 'dataType' => 'Real',
    ]])->assertOk()->json();

    expect(DB::table('properties')->where('gopID', $gopId)->count())->toBe(2);
    // A property born in a draft is a draft until the template is published.
    expect(DB::table('propertiesdatadictionaries')->where('Id', $new['dictId'])->value('status'))->toBe('Preview');
    // Live version unchanged.
    expect(DB::table('groupofproperties')->where('pdtId', $live['pdt']->Id)->count())->toBe(1);
    expect(pdtRow($live['pdt']->Id)->status)->toBe('Active');
});

// ============================================================ diff + bump

test('an unchanged draft cannot be published', function () {
    $live = F::pdt([1]);
    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];

    $diff = drafts()->diff($draftId);
    expect($diff['hasChanges'])->toBeFalse()->and($diff['bump'])->toBeNull();

    $this->actingAs(F::admin());
    $this->postJson(route('admin.previews.publishApply', ['pdt' => $draftId]))->assertStatus(422)
        ->assertJson(['ok' => false]);
    expect(pdtRow($draftId)->status)->toBe('Preview');
});

test('editing how a property is used is a revision', function () {
    $live = F::pdt([2, 1]);
    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];
    $ctx = DB::table('properties')->where('pdtID', $draftId)->orderBy('Id')->first();
    app(PreviewService::class)->editContext($ctx->Id, ['descriptionEn' => 'clarified usage']);

    $diff = drafts()->diff($draftId);
    expect($diff['bump'])->toBe('revision')
        ->and($diff['allowedBumps'])->toBe(['revision', 'version'])
        ->and($diff['next']['revision'])->toBe([2, 2])
        ->and($diff['next']['version'])->toBe([3, 0]);
    $g = collect($diff['gops'])->firstWhere('draftGopId', $ctx->gopID);
    expect($g['state'])->toBe('modified')->and($g['bump'])->toBe('revision')
        ->and($g['propsChanged'][0]['usage'])->toHaveKey('descriptionEn');
    expect(collect($diff['gops'])->where('state', 'unchanged'))->toHaveCount(1);
});

test('adding a group requires a new version', function () {
    $live = F::pdt([1]);
    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];
    app(PreviewService::class)->addGop($draftId, ['gopNameEn' => 'New', 'gopNamePt' => 'Novo']);

    $diff = drafts()->diff($draftId);
    expect($diff['bump'])->toBe('version')->and($diff['allowedBumps'])->toBe(['version'])
        ->and(collect($diff['gops'])->where('state', 'added'))->toHaveCount(1);
});

test('removing a property versions its group and revises the template', function () {
    $live = F::pdt([2]);
    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];
    app(PreviewService::class)->removeProperty(DB::table('properties')->where('pdtID', $draftId)->value('Id'));

    $diff = drafts()->diff($draftId);
    expect($diff['bump'])->toBe('revision');
    expect($diff['gops'][0]['bump'])->toBe('version')->and($diff['gops'][0]['to'])->toBe([2, 0])
        ->and($diff['gops'][0]['propsRemoved'])->toHaveCount(1);
});

test('renaming the template requires a new version', function () {
    $live = F::pdt([1]);
    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];
    app(PreviewService::class)->editPdtAttributes($draftId, ['pdtNameEn' => 'Renamed']);

    expect(drafts()->diff($draftId)['bump'])->toBe('version');
});

test('editing a shared definition forks it and shows up in the diff', function () {
    $live = F::pdt([1]);
    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];
    $ctx = DB::table('properties')->where('pdtID', $draftId)->first();

    $r = app(PreviewService::class)->editPreviewProperty($ctx->Id, ['definitionEn' => 'Better definition']);
    expect($r['forked'])->toBeTrue();
    // The live definition is untouched.
    expect(DB::table('propertiesdatadictionaries')->where('Id', $live['dicts'][0]->Id)->value('definitionEn'))->not->toBe('Better definition');

    $diff = drafts()->diff($draftId);
    expect($diff['gops'][0]['propsChanged'][0]['definition'])->toHaveKey('definitionEn');
    expect(app(PreviewService::class)->planPublish($draftId)['divergences'])->toHaveCount(1);
});

test('legacy rows with zero dates can be copied into a draft and forked', function () {
    // Every imported dictionary row carries '0000-00-00' dates; strict mode refuses to
    // write those back, which used to make any definition edit in a draft fail.
    $live = F::pdt([1]);
    DB::statement("SET SESSION sql_mode='NO_ENGINE_SUBSTITUTION'");
    DB::table('propertiesdatadictionaries')->where('Id', $live['dicts'][0]->Id)
        ->update(['dateOfCreation' => '0000-00-00', 'dateOfVersion' => '0000-00-00', 'dateOfRevision' => '0000-00-00', 'created_at' => '0000-00-00']);
    DB::table('groupofproperties')->where('pdtId', $live['pdt']->Id)->update(['dateOfLastChange' => '0000-00-00', 'created_at' => '0000-00-00']);
    DB::statement("SET SESSION sql_mode='ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION'");

    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];
    $ctx = DB::table('properties')->where('pdtID', $draftId)->first();
    $fork = app(PreviewService::class)->editPreviewProperty($ctx->Id, ['definitionEn' => 'Better definition']);
    expect($fork['forked'])->toBeTrue();

    app(PreviewService::class)->applyPublish($draftId, [$ctx->Id => 'version']);
    expect(DB::table('propertiesdatadictionaries')->where('Id', $fork['dictId'])->value('status'))->toBe('Active');
});

// ============================================================ publish

test('publishing a revision replaces the live version and keeps it as history', function () {
    $live = F::pdt([2, 1]);
    $baseId = $live['pdt']->Id;
    $draftId = drafts()->startDraft($baseId)['draftId'];
    $ctx = DB::table('properties')->where('pdtID', $draftId)->orderBy('Id')->first();
    app(PreviewService::class)->editContext($ctx->Id, ['descriptionEn' => 'clarified usage']);

    $this->actingAs(F::admin());
    $this->postJson(route('admin.previews.publishApply', ['pdt' => $draftId]))->assertOk()->assertJson(['ok' => true, 'version' => [2, 2]]);

    $new = pdtRow($draftId);
    $old = pdtRow($baseId);
    expect($new->status)->toBe('Active')->and([$new->versionNumber, $new->revisionNumber])->toBe([2, 2]);
    expect($old->status)->toBe('InActive')->and($old->depreciationExplanation)->toBe('Superseded by version 2.2');
    expect(activeRows($live['pdt']->GUID)->pluck('Id')->all())->toBe([$draftId]);

    // Old groups retire; new groups go live — the changed one renumbered, the other carried forward.
    expect(DB::table('groupofproperties')->where('pdtId', $baseId)->pluck('status')->unique()->all())->toBe(['InActive']);
    $gops = DB::table('groupofproperties')->where('pdtId', $draftId)->orderBy('Id')->get();
    expect($gops->pluck('status')->unique()->all())->toBe(['Active']);
    expect([$gops[0]->versionNumber, $gops[0]->revisionNumber])->toBe([1, 3]);
    expect([$gops[1]->versionNumber, $gops[1]->revisionNumber])->toBe([1, 2]);

    $this->get('/dashboard')->assertSee('V2.2');
});

test('a revision can be published as a version when the admin chooses so', function () {
    $live = F::pdt([1]);
    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];
    app(PreviewService::class)->editPdtAttributes($draftId, ['descriptionEn' => 'better']);

    $this->actingAs(F::admin());
    $this->postJson(route('admin.previews.publishApply', ['pdt' => $draftId]), ['bump' => 'version'])->assertOk();
    expect([pdtRow($draftId)->versionNumber, pdtRow($draftId)->revisionNumber])->toBe([3, 0]);
});

test('a change that requires a version cannot be published as a revision', function () {
    $live = F::pdt([1]);
    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];
    app(PreviewService::class)->addGop($draftId, ['gopNameEn' => 'New', 'gopNamePt' => 'Novo']);

    $this->actingAs(F::admin());
    $this->postJson(route('admin.previews.publishApply', ['pdt' => $draftId]), ['bump' => 'revision'])->assertStatus(422);
    expect(pdtRow($draftId)->status)->toBe('Preview');
    expect(pdtRow($live['pdt']->Id)->status)->toBe('Active');
});

test('publishing a forked definition as a new version retires the old definition', function () {
    $live = F::pdt([1]);
    $original = $live['dicts'][0];
    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];
    $ctx = DB::table('properties')->where('pdtID', $draftId)->first();
    $fork = app(PreviewService::class)->editPreviewProperty($ctx->Id, ['definitionEn' => 'Better definition']);

    app(PreviewService::class)->applyPublish($draftId, [$ctx->Id => 'version']);

    $forkRow = DB::table('propertiesdatadictionaries')->where('Id', $fork['dictId'])->first();
    expect($forkRow->status)->toBe('Active')->and([$forkRow->versionNumber, $forkRow->revisionNumber])->toBe([2, 0]);
    expect(DB::table('propertiesdatadictionaries')->where('Id', $original->Id)->value('status'))->toBe('InActive');
});

test('keeping the live definition discards the fork', function () {
    $live = F::pdt([1]);
    $original = $live['dicts'][0];
    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];
    $ctx = DB::table('properties')->where('pdtID', $draftId)->first();
    $fork = app(PreviewService::class)->editPreviewProperty($ctx->Id, ['definitionEn' => 'Better definition']);
    app(PreviewService::class)->editContext($ctx->Id, ['descriptionEn' => 'also a usage change']);

    app(PreviewService::class)->applyPublish($draftId, [$ctx->Id => 'keep']);

    expect(DB::table('propertiesdatadictionaries')->where('Id', $fork['dictId'])->exists())->toBeFalse();
    expect(DB::table('properties')->where('Id', $ctx->Id)->value('propertyId'))->toBe($original->Id);
    expect(DB::table('propertiesdatadictionaries')->where('Id', $original->Id)->value('status'))->toBe('Active');
});

test('publishing re-derives property statuses: dropped ones retire, new ones go live', function () {
    $live = F::pdt([2]);
    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];
    $drop = DB::table('properties')->where('pdtID', $draftId)->orderBy('Id')->first();
    app(PreviewService::class)->removeProperty($drop->Id);
    $gopId = DB::table('groupofproperties')->where('pdtId', $draftId)->value('Id');
    $new = app(PreviewService::class)->addNewProperty($gopId, [
        'nameEn' => 'Fresh', 'namePt' => 'Fresco', 'nameEnSc' => 'Fresh', 'namePtSc' => 'Fresco',
        'definitionEn' => 'd', 'definitionPt' => 'd', 'dataType' => 'String',
    ]);

    app(PreviewService::class)->applyPublish($draftId);

    expect(DB::table('propertiesdatadictionaries')->where('Id', $drop->propertyId)->value('status'))->toBe('InActive');
    expect(DB::table('propertiesdatadictionaries')->where('Id', $new['dictId'])->value('status'))->toBe('Active');
    expect(app(\App\Services\VersioningService::class)->recomputeStatuses(false))->toBe([]);
});

test('publishing a brand-new template also publishes its new construction object and properties', function () {
    $co = F::constructionObject('Preview');
    $pdtId = DB::table('productdatatemplates')->insertGetId([
        'GUID' => F::guid(), 'referenceDocumentGUID' => 'n/a', 'constructionObjectGUID' => $co,
        'pdtNameEn' => 'Brand new', 'pdtNamePt' => 'Novo', 'category' => 'Construção', 'status' => 'Preview',
        'versionNumber' => 1, 'revisionNumber' => 0, 'dateOfRevision' => '2024-01-01', 'dateOfVersion' => '2024-01-01',
    ], 'Id');
    F::fill($pdtId, [2]);

    $plan = app(PreviewService::class)->planPublish($pdtId);
    expect($plan['isVersionDraft'])->toBeFalse()->and($plan['canPublish'])->toBeTrue();

    app(PreviewService::class)->applyPublish($pdtId);

    expect(pdtRow($pdtId)->status)->toBe('Active');
    expect(DB::table('constructionobjects')->where('GUID', $co)->value('status'))->toBe('Active');
    expect(DB::table('propertiesdatadictionaries')->whereIn('Id', DB::table('properties')->where('pdtID', $pdtId)->pluck('propertyId'))
        ->pluck('status')->unique()->all())->toBe(['Active']);
});

// ============================================================ discard

test('discarding a version draft removes it and its forks, never the live version', function () {
    $live = F::pdt([2]);
    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];
    $ctx = DB::table('properties')->where('pdtID', $draftId)->first();
    $fork = app(PreviewService::class)->editPreviewProperty($ctx->Id, ['definitionEn' => 'Better definition']);

    $this->actingAs(F::admin());
    $this->postJson(route('admin.previews.deleteApply', ['pdt' => $draftId]))->assertOk();

    expect(DB::table('productdatatemplates')->where('Id', $draftId)->exists())->toBeFalse();
    expect(DB::table('groupofproperties')->where('pdtId', $draftId)->exists())->toBeFalse();
    expect(DB::table('propertiesdatadictionaries')->where('Id', $fork['dictId'])->exists())->toBeFalse();
    // Live untouched, shared definitions kept.
    expect(pdtRow($live['pdt']->Id)->status)->toBe('Active');
    expect(DB::table('properties')->where('pdtID', $live['pdt']->Id)->count())->toBe(2);
    expect(DB::table('propertiesdatadictionaries')->whereIn('Id', collect($live['dicts'])->pluck('Id'))->count())->toBe(2);
});

// ============================================================ public isolation

test('identifiers and exports resolve to the live version while a draft exists', function () {
    $live = F::pdt([1]);
    $draftId = drafts()->startDraft($live['pdt']->Id)['draftId'];
    app(PreviewService::class)->addGop($draftId, ['gopNameEn' => 'Draft only', 'gopNamePt' => 'Só rascunho']);

    expect(UriService::latestOfLineage('dt', $live['pdt']->GUID)->Id)->toBe($live['pdt']->Id);
    expect(UriService::latestOfLineage('dt', $live['pdt']->GUID, 2)->Id)->toBe($live['pdt']->Id);
    expect((new \App\Services\Iso23387Exporter())->getLatestPdt($live['pdt']->GUID)->Id)->toBe($live['pdt']->Id);

    $this->get(route('pdtsdownload', ['pdtID' => $live['pdt']->Id]))->assertOk()->assertDontSee('Draft only');
});
