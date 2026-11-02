<?php

use App\Services\DraftVersionService;
use App\Services\PreviewService;
use Illuminate\Support\Facades\DB;
use Tests\Support\PdtFixtures as F;

/*
 * Every admin page renders for an admin with realistic content (a published template,
 * a new-version draft with changes, a brand-new template draft), and a new-version draft
 * stays offline for everyone else.
 */

beforeEach(function () {
    $this->live = F::pdt([2, 1], ['pdtNameEn' => 'Clay brick', 'pdtNamePt' => 'Tijolo']);
    $this->draftId = app(DraftVersionService::class)->startDraft($this->live['pdt']->Id)['draftId'];
    $svc = app(PreviewService::class);
    $svc->addGop($this->draftId, ['gopNameEn' => 'Acoustics', 'gopNamePt' => 'Acústica']);
    $ctx = DB::table('properties')->where('pdtID', $this->draftId)->orderBy('Id')->first();
    $svc->editContext($ctx->Id, ['descriptionEn' => 'clarified']);
    $svc->editPreviewProperty($ctx->Id, ['definitionEn' => 'A sharper definition']);

    $this->newTpl = F::pdt([1], ['status' => 'Preview', 'pdtNameEn' => 'Brand new thing', 'versionNumber' => 1, 'revisionNumber' => 0]);
    $this->actingAs(F::admin());
});

test('admin hub lists templates, drafts and the health summary', function () {
    $this->get(route('admin'))->assertOk()
        ->assertSee('Drafts in progress')->assertSee('Clay brick')->assertSee('draft in progress')
        ->assertSee('Continue draft')->assertSee('Brand new thing')->assertSee('Status health check');
});

test('drafts page tells new versions and new templates apart', function () {
    $this->get(route('admin.previews'))->assertOk()
        ->assertSee('New version')->assertSee('of live')->assertSee('v2.1')->assertSee('New template');
});

test('editor shows the draft as an offline new version with its changes', function () {
    $this->get(route('admin.previews.editor', ['pdt' => $this->draftId]))->assertOk()
        ->assertSee('New version draft')->assertSee('based on live')
        ->assertSee('Changes so far')->assertSee('1 group(s) added')
        ->assertSee('Acoustics')->assertSee('new group')->assertSee('draft definition')
        ->assertSee('Review &amp; publish', false);
});

test('editor works for a brand-new template draft', function () {
    $this->get(route('admin.previews.editor', ['pdt' => $this->newTpl['pdt']->Id]))->assertOk()
        ->assertSee('New template draft')->assertDontSee('Changes so far');
});

test('review page shows the version decision, the diff and the edited definition', function () {
    $this->get(route('admin.previews.review', ['pdt' => $this->draftId]))->assertOk()
        ->assertSee('will publish as')->assertSee('v3.0')
        ->assertSee('required by your changes')->assertSee('Not possible')
        ->assertSee('Acoustics')->assertSee('clarified')
        ->assertSee('Edited dictionary definitions')->assertSee('A sharper definition')
        ->assertSee('becomes', false);
});

test('review page of an unchanged draft offers nothing to publish', function () {
    $other = F::pdt([1]);
    $draftId = app(DraftVersionService::class)->startDraft($other['pdt']->Id)['draftId'];

    $this->get(route('admin.previews.review', ['pdt' => $draftId]))->assertOk()
        ->assertSee('No changes yet')->assertDontSee('id="btn-publish"', false);
});

test('new-version confirmation page explains the flow before anything is written', function () {
    $other = F::pdt([1], ['pdtNameEn' => 'Timber beam']);

    $this->get(route('admin.pdt.newVersion', ['pdt' => $other['pdt']->Id]))->assertOk()
        ->assertSee('New version of “Timber beam”', false)->assertSee('Create draft');
    expect(DB::table('productdatatemplates')->where('GUID', $other['pdt']->GUID)->count())->toBe(1);
});

test('status check page renders proposed fixes and issues', function () {
    // An Active dictionary row used only by a draft: proposed -> Preview.
    DB::table('propertiesdatadictionaries')->where('Id', $this->newTpl['dicts'][0]->Id)->update(['status' => 'Active']);

    $this->get(route('admin.statusCheck'))->assertOk()
        ->assertSee('Dictionary properties')->assertSee('Only used by drafts')->assertSee('Apply selected');
});

test('a new-version draft is offline for the public, but visible to admins', function () {
    auth()->logout();
    $this->get(route('pdtsdownload', ['pdtID' => $this->draftId]))->assertNotFound();
    $this->getJson('/api/' . $this->draftId)->assertNotFound();
    $this->post(route('pdt.export.json', ['pdtId' => $this->draftId]))->assertNotFound();
    // The published version stays reachable.
    $this->get(route('pdtsdownload', ['pdtID' => $this->live['pdt']->Id]))->assertOk();

    $this->actingAs(F::admin());
    $this->get(route('pdtsdownload', ['pdtID' => $this->draftId]))->assertOk();
});
