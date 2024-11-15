<?php

use App\Http\Controllers\{
    ContactController,
    UserController,
    ProfileController,
    ProductdatatemplatesController,
    GroupofpropertiesController,
    PropertiesdatadictionariesController,
    PropertiesController,
    ReferencedocumentsController,
    LoinsController
};
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

// Public Pages
Route::get('/', fn() => view('welcome'))->name('home');
Route::get('/apidoc', fn() => view('apidoc'))->name('apidoc');
Route::get('/knowledge', fn() => view('knowledge'))->name('knowledge');
Route::get('/participantes', fn() => view('participantes'))->name('participantes');
Route::get('/privacypolicy', fn() => view('privacypolicy'))->name('privacypolicy');
Route::get('/contact', fn() => view('contact'));
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

// Dashboard - Requires Authentication
Route::get('/dashboard', [ProductdatatemplatesController::class, 'getLatestPDTs'])->name('dashboard');

// PDTs and Survey Routes
Route::get('/pdtsdownload/{pdtID}', [GroupofpropertiesController::class, 'getGroupOfProperties'])->name('pdtsdownload');
Route::get('/pdtssurvey/{pdtID}', [GroupofpropertiesController::class, 'getGroupOfProperties2'])->middleware(['auth', 'verified'])->name('pdtssurvey');
Route::post('/pdtssurvey/saveAnswers', [GroupofpropertiesController::class, 'saveAnswers'])->middleware(['auth', 'verified'])->name('saveAnswers');
Route::post('/pdtssurvey/{pdtID}', [GroupofpropertiesController::class, 'store'])->middleware(['auth', 'verified']);
Route::post('/pdtssurvey/store', [GroupofpropertiesController::class, 'store'])->middleware(['auth', 'verified'])->name('pdtssurveystore');

// Data Dictionary and Reference Document Views
Route::get('/datadictionaryview/{propID}-{propGUID}', [PropertiesdatadictionariesController::class, 'getPropertyDataDictionary'])->name('datadictionaryview');
Route::get('/datadictionaryviewGOP/{gopID}-{gopGUID}', [GroupofpropertiesController::class, 'getGOPDataDictionary'])->name('datadictionaryviewGOP');
Route::get('/referencedocumentview/{rdGUID}', [ReferencedocumentsController::class, 'getReferenceDocument'])->name('referencedocumentview');

// Comments and Feedback
Route::post('/comments/{propID}', [GroupofpropertiesController::class, 'getCommentProperty']);
Route::delete('/deletefeedback', [GroupofpropertiesController::class, 'destroyfeedback']);

// LOIN Project Routes - Requires Authentication
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/loinproject', [LoinsController::class, 'projectsindex'])->name('loinproject');
    Route::get('/loinproject/create', [LoinsController::class, 'projectscreate'])->name('projectscreate');
    Route::post('/loinproject/store', [LoinsController::class, 'projectsstore'])->name('projectsstore');
    Route::delete('/loinproject/{project}', [LoinsController::class, 'destroyproject'])->name('projectsdestroy');

    Route::get('/loinattributes/{project}', [LoinsController::class, 'loinattributes'])->name('loinattributes');
    Route::post('/loinattributes/{project}', [LoinsController::class, 'loinattributesstore'])->name('loinattributesstore');
    Route::post('/loinattributes/{projectId}/delete', [LoinsController::class, 'deleteAttribute'])->name('loinattributesdelete');

    Route::get('/loincreate1/{project}', [LoinsController::class, 'createLoin'])->name('loincreate1');
    Route::match(['get', 'post'], '/createLoin2', [LoinsController::class, 'createloin2'])->name('loincreate2');
    Route::post('/loincreate2/store', [LoinsController::class, 'createLoin2store'])->name('createLoin2store');
    Route::get('/search-properties', [LoinsController::class, 'searchProperties'])->name('searchProperties');

    Route::get('/loinView/{loinId}', [LoinsController::class, 'loinInstance'])->name('loinView');
    Route::delete('/loinDelete/{loinId}', [LoinsController::class, 'destroyLoin'])->name('loinDelete');
    Route::get('/loinViewProject/{projectId}', [LoinsController::class, 'showLoinsByProject'])->name('loinViewProject');

    Route::get('/loinDownloadJSON/{id}', [LoinsController::class, 'downloadJSON'])->name('loinDownloadJSON');
    Route::get('/loinDownloadExcel/{id}/{objectName}', [LoinsController::class, 'downloadExcel'])->name('loinDownloadExcel');
    Route::get('/projectLoinsExcel/{projectName}', [LoinsController::class, 'exportProjectLoinsExcel'])->name('exportProjectLoinsExcel');
    Route::get('/projectLoinsJson/{projectName}', [LoinsController::class, 'exportProjectLoinsJson'])->name('exportProjectLoinsJson');
});

// Profile Management - Requires Authentication
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])->name('profile.updatePhoto');
    Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])->name('profile.deletePhoto');
    Route::post('/updateSubscription', [ProfileController::class, 'updateSubscription'])->name('profile.updateSubscription');
});

// Admin Routes - Restricted to Admins
Route::middleware(['auth', 'verified', 'admin'])->group(function () {
    Route::get('/admin', [UserController::class, 'index'])->name('admin');

    // Export Routes
    Route::get('/exportdomainbsdd', fn() => view('exportdomainbsdd'))->name('exportdomainbsdd');
    Route::post('/exportdomainbsdd-groups', [ProductDataTemplatesController::class, 'exportDataToJson'])->name('productdatatemplates.exportJson');
    Route::post('/exportdomainbsdd-psets', [ProductDataTemplatesController::class, 'exportDataToJsonPSETS'])->name('productdatatemplates.exportJsonPSETS');

    Route::post('/admin/update', [UserController::class, 'updateUsers'])->name('update.users');

    // Product Data Templates Management
    Route::get('/pdtinput', fn() => view('pdtinput'))->name('pdtinput');
    Route::get('/productdatatemplates/create', [ProductDataTemplatesController::class, 'create'])->name('productdatatemplates.create');
    Route::post('/productdatatemplates/store', [ProductDataTemplatesController::class, 'store'])->name('productdatatemplates.store');

    // Group of Properties Management
    Route::get('/groupofproperties/choose_pdt', [GroupOfPropertiesController::class, 'createStep1'])->name('groupofproperties.choose_pdt');
    Route::get('/groupofproperties/creategop', [GroupOfPropertiesController::class, 'createStep2'])->name('groupofproperties.creategop');
    Route::post('/groupofproperties/creategop', [GroupOfPropertiesController::class, 'createStep2']);
    Route::post('/groupofproperties/storegop', [GroupOfPropertiesController::class, 'storegop'])->name('groupofproperties.storegop');

    // Properties Management
    Route::get('/properties/choose_pdt', [PropertiesController::class, 'choosePDT'])->name('properties.choose_pdt');
    Route::match(['get', 'post'], '/properties/createprops', [PropertiesController::class, 'createprops'])->name('properties.createprops');
    Route::post('/properties/addNew', [PropertiesController::class, 'PropertiesAdded'])->name('properties.addNew');
    Route::post('/properties/addNewProperty', [PropertiesController::class, 'addPropertyManual'])->name('properties.addPropertyManual');
    Route::post('/properties/addNewPropertyFromDictionary', [PropertiesController::class, 'addFromDictionary'])->name('properties.addFromDataDictionary');
    Route::post('/properties/addFromDictionary', [PropertiesController::class, 'PropertiesAddedDictionaryPage'])->name('properties.addFromDictionary');
    Route::get('/properties/edit/{propertyId}', [PropertiesController::class, 'showProperty'])->name('properties.edit');
    Route::post('/properties/edit/{propertyId}', [PropertiesController::class, 'updateProperty'])->name('properties.update');

    // Data Dictionary Properties Management
    Route::get('/properties/editdd/{propertyddId}', [PropertiesdatadictionariesController::class, 'showddProperty'])->name('properties.editdd');
    Route::post('/properties/editdd/{propertyddId}', [PropertiesdatadictionariesController::class, 'updateddProperty'])->name('properties.updateddProperty');
});

// Authentication Routes
require __DIR__ . '/auth.php';
