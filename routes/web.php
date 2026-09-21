<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductdatatemplatesController;
use App\Http\Controllers\GroupofpropertiesController;
use App\Http\Controllers\ReferencedocumentsController;
use App\Http\Controllers\DictionaryDedupeController;
use App\Http\Controllers\ReferenceDocumentDedupeController;
use App\Http\Controllers\PreviewWorkflowController;
use App\Http\Controllers\PdtVersioningController;
use App\Http\Controllers\PdtCreateController;
use App\Http\Controllers\ActivePdtEditController;
use App\Http\Controllers\PropertyPickerController;
use App\Http\Controllers\AdminLookupController;
use App\Http\Controllers\RelationshipController;
use App\Http\Controllers\PropertyDependencyController;
use App\Http\Controllers\UriController;
use App\Support\Lang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::get('/apidoc', function () {
    return view('apidoc');
})->name('apidoc');

Route::get('/knowledge', function () {
    return view('knowledge');
})->name('knowledge');

Route::get('/participantes', function () {
    return view('participantes');
})->name('participantes');

Route::get('/privacypolicy', function () {
    return view('privacypolicy');
})->name('privacypolicy');

Route::get('/contact',  function () {
    return view('contact');
});

Route::get('/manifestobuildingsmartpt', function () {
    return view('manifestobuildingsmartpt');
})->name('manifestobuildingsmartpt');

Route::post('/contact', [ContactController::class, 'store'])
    ->name('contact.store');



Route::get('/dashboard', [ProductdatatemplatesController::class, 'getLatestPDTs'], function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/pdtsdownload/{pdtID}', [GroupofpropertiesController::class, 'getGroupOfProperties'])
    ->name('pdtsdownload');

// ---------------------------------------------------------------------------
// Identifiers. Every dictionary record is reached through exactly one scheme:
//     /uri/{dictVersion}/{entity}/{code}
//     /uri/{dictVersion}/{entity}/{code}/v{versionNumber}
// entity is one of prop, dt, class, gop, classprop, doc, unit, pq, dim, enum.
// {code} captures the remainder so unit symbols containing "/" (e.g. kg/m³) and the
// optional /v{n} suffix are split by UriService, not by the router. "latest" is
// accepted as dictVersion and redirects to the current release.
// These replace the old /pdtview, /datadictionaryview, /datadictionaryviewGOP,
// /classpropertyview, /referencedocumentview, /unit, /quantitykind and /dimension paths.
// ---------------------------------------------------------------------------
Route::get('/uri/{dictVersion}/{entity}/{code}', [UriController::class, 'resolve'])
    ->name('uri.resolve')
    ->where('dictVersion', '[0-9A-Za-z.]+')
    ->where('entity', '[a-z]+')
    ->where('code', '.+');

// Language toggle (PT/EN), remembered for the session. ?lang=pt|en on any page also works.
Route::get('/language/{locale}', function (Request $request, string $locale) {
    Lang::set($locale);

    // Only return into this site, and drop any ?lang= so it cannot override the choice.
    $to = (string) $request->query('redirect', '');
    if ($to === '' || !str_starts_with($to, url('/'))) {
        return redirect(url()->previous(url('/')));
    }
    $parts = parse_url($to);
    parse_str($parts['query'] ?? '', $query);
    unset($query['lang']);

    return redirect(strtok($to, '?')
        . ($query ? '?' . http_build_query($query) : '')
        . (isset($parts['fragment']) ? '#' . $parts['fragment'] : ''));
})->name('language.switch')->where('locale', 'pt|en');

// Single PDT export endpoints (EN ISO 23387 format)
Route::post('/pdt-export/json/{pdtId}', [ProductdatatemplatesController::class, 'downloadPdtJson'])
    ->name('pdt.export.json');

Route::post('/pdt-export/xml/{pdtId}', [ProductdatatemplatesController::class, 'downloadPdtXml'])
    ->name('pdt.export.xml');

Route::middleware('auth', 'verified')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])
    ->middleware(['auth', 'verified'])->name('profile.deletePhoto');
Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])
    ->middleware(['auth', 'verified'])->name('profile.updatePhoto');
Route::post('/updateSubscription', [ProfileController::class, 'updateSubscription'])
    ->middleware(['auth', 'verified'])->name('profile.updateSubscription');

require __DIR__ . '/auth.php';

Route::get('/pdtssurvey/{pdtID}', [GroupofpropertiesController::class, 'getGroupOfProperties2'])
    ->whereNumber('pdtID')   // otherwise a GET to the POST-only /pdtssurvey/saveAnswers matched here
    ->middleware(['auth', 'verified'])->name('pdtssurvey');
Route::post('/pdtssurvey/saveAnswers', [GroupofpropertiesController::class, 'saveAnswers'])
    ->middleware(['auth', 'verified'])->name('saveAnswers');
Route::post('/pdtssurvey/{pdtID}', [GroupofpropertiesController::class, 'store'])
    ->middleware(['auth', 'verified']);
Route::post('/pdtssurvey/store', [GroupofpropertiesController::class, 'store'])
    ->middleware(['auth', 'verified'])->name('pdtssurveystore');


// The whole ISO 23387 reference layer — units, quantity kinds AND dimensions — is
// addressed through the identifier scheme above (/uri/{v}/unit, /uri/{v}/pq,
// /uri/{v}/dim). JSON via Accept, ?format=json, or a .json suffix.


Route::post('/comments/{propID}', [GroupofpropertiesController::class, 'getCommentProperty']);

Route::delete('/deletefeedback', [GroupofpropertiesController::class, 'destroyfeedback']);


// Routes accessible only to admins
Route::group(['middleware' => 'auth', 'verified', 'admin'], function () {

    Route::get('/admin', [UserController::class, 'index'])
        ->name('admin');

    // Route for exporting JSON
    Route::get('/exportdomainbsdd', function () {
        // Show the identifier collisions up front: the export refuses to run while any
        // code is claimed by two records, so the admin sees why before clicking.
        return view('exportdomainbsdd', [
            'collisions' => \App\Services\UriService::collisions('export'),
        ]);
    })->name('exportdomainbsdd');

    Route::post('/exportdomainbsdd-psets', [ProductDataTemplatesController::class, 'exportDataToJsonPSETS'])
        ->name('productdatatemplates.exportJsonPSETS');


    Route::post('/admin/update', [UserController::class, 'updateUsers'])
        ->name('update.users');

    // Interactive dictionary deduplication review tool
    Route::get('/admin/dedupe-dictionary', [DictionaryDedupeController::class, 'index'])
        ->name('admin.dedupe');
    Route::get('/admin/dedupe-dictionary/group', [DictionaryDedupeController::class, 'group'])
        ->name('admin.dedupe.group');
    Route::post('/admin/dedupe-dictionary/apply', [DictionaryDedupeController::class, 'apply'])
        ->name('admin.dedupe.apply');
    Route::post('/admin/dedupe-dictionary/property', [DictionaryDedupeController::class, 'updateProperty'])
        ->name('admin.dedupe.property');
    // Deduped/shared properties: dictionary definition + all in-context descriptions, editable.
    Route::get('/admin/dedupe-dictionary/deduped', [DictionaryDedupeController::class, 'deduped'])
        ->name('admin.dedupe.deduped');
    Route::post('/admin/dedupe-dictionary/dict', [DictionaryDedupeController::class, 'updateDict'])
        ->name('admin.dedupe.dict');
    Route::post('/admin/dedupe-dictionary/review-state', [DictionaryDedupeController::class, 'reviewState'])
        ->name('admin.dedupe.reviewState');

    // Reference-document deduplication: same tool, different table. Documents duplicated
    // under one name hand out one identifier to two records; documents that merely share
    // a title are usually amendments of a standard and are shown for review only.
    Route::get('/admin/dedupe-reference-documents', [ReferenceDocumentDedupeController::class, 'index'])
        ->name('admin.dedupeRefDocs');
    Route::get('/admin/dedupe-reference-documents/group', [ReferenceDocumentDedupeController::class, 'group'])
        ->name('admin.dedupeRefDocs.group');
    Route::post('/admin/dedupe-reference-documents/apply', [ReferenceDocumentDedupeController::class, 'apply'])
        ->name('admin.dedupeRefDocs.apply');

    // Interactive API tester (admin). Calls the site's own origin by default so it
    // behaves identically on localhost and once live on pdts.pt.
    Route::get('/admin/api-tester', function () {
        // Seed the identifier fields with codes that actually exist, so "Run all" works
        // out of the box instead of 404-ing on invented examples.
        return view('admin.api-tester', ['sample' => \App\Services\UriService::sampleCodes()]);
    })->name('admin.api-tester');

    // Preview workflow: free-edit drafts (status = Preview), hard-delete, publish.
    Route::get('/admin/previews', [PreviewWorkflowController::class, 'drafts'])
        ->name('admin.previews');
    Route::post('/admin/previews/create', [PreviewWorkflowController::class, 'createDraft'])
        ->name('admin.previews.create');
    Route::get('/admin/previews/{pdt}', [PreviewWorkflowController::class, 'editor'])
        ->whereNumber('pdt')->name('admin.previews.editor');

    // Free-edit AJAX (write in place)
    Route::post('/admin/previews/{pdt}/pdt', [PreviewWorkflowController::class, 'editPdt'])->whereNumber('pdt')->name('admin.previews.editPdt');
    Route::post('/admin/previews/{pdt}/gop', [PreviewWorkflowController::class, 'editGop'])->whereNumber('pdt')->name('admin.previews.editGop');
    Route::post('/admin/previews/{pdt}/gop/add', [PreviewWorkflowController::class, 'addGop'])->whereNumber('pdt')->name('admin.previews.addGop');
    Route::get('/admin/previews/{pdt}/gop/suggestions', [PreviewWorkflowController::class, 'gopSuggestions'])->whereNumber('pdt')->name('admin.previews.gopSuggestions');
    Route::post('/admin/previews/{pdt}/gop/remove', [PreviewWorkflowController::class, 'removeGop'])->whereNumber('pdt')->name('admin.previews.removeGop');
    Route::post('/admin/previews/{pdt}/context', [PreviewWorkflowController::class, 'editContext'])->whereNumber('pdt')->name('admin.previews.editContext');
    Route::post('/admin/previews/{pdt}/context/remove', [PreviewWorkflowController::class, 'removeContext'])->whereNumber('pdt')->name('admin.previews.removeContext');
    Route::post('/admin/previews/{pdt}/property/edit', [PreviewWorkflowController::class, 'editProperty'])->whereNumber('pdt')->name('admin.previews.editProperty');
    Route::post('/admin/previews/{pdt}/property/add-existing', [PreviewWorkflowController::class, 'addExistingProperty'])->whereNumber('pdt')->name('admin.previews.addExisting');
    Route::post('/admin/previews/{pdt}/property/add-new', [PreviewWorkflowController::class, 'addNewProperty'])->whereNumber('pdt')->name('admin.previews.addNew');

    // Hard delete (plan -> confirm -> apply)
    Route::get('/admin/previews/{pdt}/delete-plan', [PreviewWorkflowController::class, 'deletePlan'])->whereNumber('pdt')->name('admin.previews.deletePlan');
    Route::post('/admin/previews/{pdt}/delete', [PreviewWorkflowController::class, 'deleteApply'])->whereNumber('pdt')->name('admin.previews.deleteApply');

    // Publish (plan -> per-element divergence decision -> apply)
    Route::get('/admin/previews/{pdt}/publish-plan', [PreviewWorkflowController::class, 'publishPlan'])->whereNumber('pdt')->name('admin.previews.publishPlan');
    Route::post('/admin/previews/{pdt}/publish', [PreviewWorkflowController::class, 'publishApply'])->whereNumber('pdt')->name('admin.previews.publishApply');

    // Shared inline "create reference document" (from any editor's ref-doc field)
    Route::post('/admin/reference-documents/create-ajax', [ReferencedocumentsController::class, 'createAjax'])->name('admin.refdoc.createAjax');

    // Shared "add from existing" lookups (Preview editor + versioning editor)
    Route::get('/admin/lookup/properties', [AdminLookupController::class, 'properties'])->name('admin.lookup.properties');
    Route::get('/admin/lookup/gops', [AdminLookupController::class, 'gops'])->name('admin.lookup.gops');

    // Generic self-referential relationships (EN ISO 23387:2025 R-23387-7).
    Route::get('/admin/relations/search/{entityType}', [RelationshipController::class, 'search'])->name('admin.relations.search');
    Route::get('/admin/relations/{entityType}/{guid}', [RelationshipController::class, 'index'])->name('admin.relations.index')->where('guid', '[0-9a-fA-F]{32}');
    Route::post('/admin/relations', [RelationshipController::class, 'store'])->name('admin.relations.store');
    Route::post('/admin/relations/reorder', [RelationshipController::class, 'reorder'])->name('admin.relations.reorder');
    Route::delete('/admin/relations/{id}', [RelationshipController::class, 'destroy'])->whereNumber('id')->name('admin.relations.destroy');

    // Property dependencies (EN ISO 23387:2025 R-23387-8).
    Route::get('/admin/property-dependencies/{guid}', [PropertyDependencyController::class, 'index'])->name('admin.propdeps.index')->where('guid', '[0-9a-fA-F]{32}');
    Route::post('/admin/property-dependencies', [PropertyDependencyController::class, 'store'])->name('admin.propdeps.store');
    Route::delete('/admin/property-dependencies/{id}', [PropertyDependencyController::class, 'destroy'])->whereNumber('id')->name('admin.propdeps.destroy');

    // CREATE mode: new PDT from a construction object (select/create) -> Preview draft.
    Route::get('/admin/pdt/create', [PdtCreateController::class, 'create'])->name('admin.pdt.create');
    Route::post('/admin/pdt/create', [PdtCreateController::class, 'store'])->name('admin.pdt.create.store');

    // Shared property picker (Active-only, descriptions, exact-nameEn Excel match, gap export).
    Route::get('/admin/picker/properties', [PropertyPickerController::class, 'properties'])->name('admin.picker.properties');
    Route::post('/admin/picker/match', [PropertyPickerController::class, 'matchExcel'])->name('admin.picker.match');
    Route::post('/admin/picker/gap', [PropertyPickerController::class, 'exportGap'])->name('admin.picker.gap');

    // Mode 2: limited in-place edits on an Active PDT (no versioning).
    Route::get('/admin/pdt/{pdt}/edit', [ActivePdtEditController::class, 'editor'])->whereNumber('pdt')->name('admin.pdt.activeEdit');
    Route::post('/admin/pdt/{pdt}/edit/context', [ActivePdtEditController::class, 'updateContext'])->whereNumber('pdt')->name('admin.pdt.active.context');
    Route::post('/admin/pdt/{pdt}/edit/mapping', [ActivePdtEditController::class, 'updateDictMapping'])->whereNumber('pdt')->name('admin.pdt.active.mapping');

    // Staged "create new version" editor for Active PDTs (plan -> diff preview -> commit).
    Route::get('/admin/pdt/{pdt}/new-version', [PdtVersioningController::class, 'editor'])->whereNumber('pdt')->name('admin.pdt.newVersion');
    Route::post('/admin/pdt/{pdt}/new-version/preview', [PdtVersioningController::class, 'preview'])->whereNumber('pdt')->name('admin.pdt.newVersion.preview');
    Route::post('/admin/pdt/{pdt}/new-version/commit', [PdtVersioningController::class, 'commit'])->whereNumber('pdt')->name('admin.pdt.newVersion.commit');

    // [Legacy "Create / Edit PDTs" tool removed — superseded by the unified editor
    //  (CREATE / Active edit / Preview / New-version). Its routes, views, controller
    //  methods and admin button were deleted. Shared controllers/export/API/comments kept.]

    // add reference documents
    Route::get('/referencedocuments/list', [ReferenceDocumentsController::class, 'getReferenceDocuments'])->name('referencedocuments.list');
    Route::post('/referencedocuments/create', [ReferenceDocumentsController::class, 'referenceDocumentCreate'])->name('referencedocuments.create');
});
