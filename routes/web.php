<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductdatatemplatesController;
use App\Http\Controllers\GroupofpropertiesController;
use App\Http\Controllers\PropertiesdatadictionariesController;
use App\Http\Controllers\PropertiesController;
use App\Http\Controllers\ReferencedocumentsController;
use App\Http\Controllers\DictionaryDedupeController;
use App\Http\Controllers\PreviewWorkflowController;
use App\Http\Controllers\PdtVersioningController;
use App\Http\Controllers\PdtCreateController;
use App\Http\Controllers\ActivePdtEditController;
use App\Http\Controllers\PropertyPickerController;
use App\Http\Controllers\AdminLookupController;
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

// PDT View Page
Route::get('/pdtview/{idSlug}', [ProductdatatemplatesController::class, 'viewPdt'])
    ->name('pdtview')
    ->where('idSlug', '[0-9]+-.*');

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
    ->middleware(['auth', 'verified'])->name('pdtssurvey');
Route::post('/pdtssurvey/saveAnswers', [GroupofpropertiesController::class, 'saveAnswers'])
    ->middleware(['auth', 'verified'])->name('saveAnswers');
Route::post('/pdtssurvey/{pdtID}', [GroupofpropertiesController::class, 'store'])
    ->middleware(['auth', 'verified']);
Route::post('/pdtssurvey/store', [GroupofpropertiesController::class, 'store'])
    ->middleware(['auth', 'verified'])->name('pdtssurveystore');


Route::get(
    '/classpropertyview/{idSlug}',
    [PropertiesController::class, 'getClassPropertyView']
)->name('classpropertyview')
    ->where('idSlug', '[0-9]+-.*');

Route::get(
    '/datadictionaryview/{idSlug}',
    [PropertiesdatadictionariesController::class, 'getPropertyDataDictionary']
)->name('datadictionaryview')
    ->where('idSlug', '[0-9]+-.*');

Route::get(
    '/datadictionaryviewGOP/{idSlug}',
    [GroupofpropertiesController::class, 'getGOPDataDictionary']
)->name('datadictionaryviewGOP')
    ->where('idSlug', '[0-9]+-.*');

Route::get(
    '/referencedocumentview/{rdGUID}',
    [ReferencedocumentsController::class, 'getReferenceDocument']
)->name('referencedocumentview');


Route::post('/comments/{propID}', [GroupofpropertiesController::class, 'getCommentProperty']);

Route::delete('/deletefeedback', [GroupofpropertiesController::class, 'destroyfeedback']);


// Routes accessible only to admins
Route::group(['middleware' => 'auth', 'verified', 'admin'], function () {

    Route::get('/admin', [UserController::class, 'index'])
        ->name('admin');

    // Route for exporting JSON
    Route::get('/exportdomainbsdd', function () {
        return view('exportdomainbsdd');
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
