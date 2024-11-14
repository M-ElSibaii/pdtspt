<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductdatatemplatesController;
use App\Http\Controllers\GroupofpropertiesController;
use App\Http\Controllers\PropertiesdatadictionariesController;
use App\Http\Controllers\PropertiesController;
use App\Http\Controllers\ReferencedocumentsController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LoinsController;

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

Route::post('/contact', [ContactController::class, 'store'])
    ->name('contact.store');

Route::get('/dashboard', [ProductdatatemplatesController::class, 'getLatestPDTs'], function () {
    return view('dashboard');
})->name('dashboard');

Route::get('/pdtsdownload/{pdtID}', [GroupofpropertiesController::class, 'getGroupOfProperties'])->middleware(['auth', 'verified'])->name('pdtsdownload');

Route::get('/loinproject', [LoinsController::class, 'projectsindex'])->name('loinproject');
Route::get('/loinproject/create', [LoinsController::class, 'projectscreate'])->name('projectscreate');
Route::post('/loinproject/store', [LoinsController::class, 'projectsstore'])->name('projectsstore');
Route::delete('/loinproject/{project}', [LoinsController::class, 'destroyproject'])->name('projectsdestroy');
//Route::delete('/loinattributes/{projectId}/{type}/{id}', [LoinsController::class, 'destroyattribute'])->name('loinsattributedestroy');
Route::post('/loinattributes/{projectId}/delete', [LoinsController::class, 'deleteAttribute'])->name('loinattributesdelete');
Route::get('/loinattributes/{project}', [LoinsController::class, 'loinattributes'])->name('loinattributes');
Route::post('/loinattributes/{project}', [LoinsController::class, 'loinattributesstore'])->name('loinattributesstore');
Route::get('/loincreate1/{project}', [LoinsController::class, 'createLoin'])->name('loincreate1');

Route::post('/loincreate2/store', [LoinsController::class, 'createLoin2store'])->middleware(['auth', 'verified'])->name('createLoin2store');
Route::match(['get', 'post'], '/createLoin2',  [LoinsController::class, 'createloin2'])->middleware(['auth', 'verified'])->name('loincreate2');
Route::get('/search-properties', [LoinsController::class, 'searchProperties'])->middleware(['auth', 'verified'])->name('searchProperties');

Route::get('/loinView/{loinId}', [LoinsController::class, 'loinInstance'])->middleware(['auth', 'verified'])->name('loinView');
Route::delete('/loinDelete/{loinId}', [LoinsController::class, 'destroyLoin'])->middleware(['auth', 'verified'])->name('loinDelete');
Route::get('/loinViewProject/{projectId}', [LoinsController::class, 'showLoinsByProject'])->middleware(['auth', 'verified'])->name('loinViewProject');

Route::get('/loinDownloadJSON/{id}', [LoinsController::class, 'downloadJSON'])->middleware(['auth', 'verified'])->name('loinDownloadJSON');
Route::get('/loinDownloadExcel/{id}/{objectName}', [LoinsController::class, 'downloadExcel'])->middleware(['auth', 'verified'])->name('loinDownloadExcel');

Route::get('/projectLoinsExcel/{projectName}', [LoinsController::class, 'exportProjectLoinsExcel'])->name('exportProjectLoinsExcel');
Route::get('/projectLoinsJson/{projectName}', [LoinsController::class, 'exportProjectLoinsJson'])->name('exportProjectLoinsJson');
Route::get('/pdtsdownload/{pdtID}', [GroupofpropertiesController::class, 'getGroupOfProperties'])
    ->name('pdtsdownload');

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
    '/datadictionaryview/{propID}-{propGUID}',
    [PropertiesdatadictionariesController::class, 'getPropertyDataDictionary']
)->name('datadictionaryview');

Route::get(
    '/datadictionaryviewGOP/{gopID}-{gopGUID}',
    [GroupofpropertiesController::class, 'getGOPDataDictionary']
)->name('datadictionaryviewGOP');

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

    Route::post('/exportdomainbsdd-groups', [ProductDataTemplatesController::class, 'exportDataToJson'])
        ->name('productdatatemplates.exportJson');

    Route::post('/exportdomainbsdd-psets', [ProductDataTemplatesController::class, 'exportDataToJsonPSETS'])
        ->name('productdatatemplates.exportJsonPSETS');


    Route::post('/admin/update', [UserController::class, 'updateUsers'])
        ->name('update.users');

    Route::get('/pdtinput',  function () {
        return view('pdtinput');
    })
        ->name('pdtinput');

    // Route for creating data templates
    Route::get('/productdatatemplates/create', [ProductDataTemplatesController::class, 'create'])
        ->name('productdatatemplates.create');
    Route::post('/productdatatemplates/store', [ProductDataTemplatesController::class, 'store'])
        ->name('productdatatemplates.store');

    // Route for creating groups of properties
    Route::get('/groupofproperties/choose_pdt', [GroupOfPropertiesController::class, 'createStep1'])
        ->name('groupofproperties.choose_pdt');
    Route::get('/groupofproperties/creategop', [GroupOfPropertiesController::class, 'createStep2'])
        ->name('groupofproperties.creategop');
    Route::post('/groupofproperties/creategop', [GroupOfPropertiesController::class, 'createStep2'])
        ->name('groupofproperties.creategop');
    Route::post('/groupofproperties/storegop', [GroupOfPropertiesController::class, 'storegop'])
        ->name('groupofproperties.storegop');

    // Route for creating properties
    Route::get('/properties/choose_pdt', [PropertiesController::class, 'choosePDT'])
        ->name('properties.choose_pdt');

    Route::match(['get', 'post'], '/properties/createprops', [PropertiesController::class, 'createprops'])->name('properties.createprops');
    // Route to add new property manually
    Route::post('/properties/addNew', [PropertiesController::class, 'PropertiesAdded'])->name('properties.addNew');
    Route::post('/properties/addNewProperty', [PropertiesController::class, 'addPropertyManual'])->name('properties.addPropertyManual');

    // Route to add properties from data dictionary

    Route::post('/properties/addNewPropertyFromDictionary', [PropertiesController::class, 'addFromDictionary'])->name('properties.addFromDataDictionary');
    Route::post('/properties/addFromDictionary', [PropertiesController::class, 'PropertiesAddedDictionaryPage'])->name('properties.addFromDictionary');
    // edit properties

    Route::post('/properties/edit/{propertyId}', [PropertiesController::class, 'updateProperty'])->name('properties.update');
    Route::get('/properties/edit/{propertyId}', [PropertiesController::class, 'showProperty'])->name('properties.edit');

    // edit properties in data dictionary

    Route::post('/properties/editdd/{propertyddId}', [PropertiesdatadictionariesController::class, 'updateddProperty'])->name('properties.updatedd');
    Route::get('/properties/editdd/{propertyddId}', [PropertiesdatadictionariesController::class, 'showddProperty'])->name('properties.editdd');
});
