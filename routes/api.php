<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ContactController;
use App\Models\productdatatemplates;
use App\Models\properties;
use App\Models\propertiesdatadictionaries;
use App\Models\referencedocuments;
use App\Models\groupofproperties;
use App\Http\Controllers\ProductdatatemplatesController;
use App\Http\Controllers\GroupofpropertiesController;
use App\Http\Controllers\PropertiesdatadictionariesController;
use App\Http\Controllers\ReferencedocumentsController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
Route::post('contact', [ContactController::class, 'store'])->name('contact.store');
Route::get('/', function () {
    return view('welcome');
});

Route::get('/productDataTemplates', [ProductdatatemplatesController::class, 'productDataTemplates']);

Route::get('/dataDictionary', [ProductdatatemplatesController::class, 'dataDictionary']);

Route::get('/dataDictionary/{Id}', [ProductdatatemplatesController::class, 'propertyInDataDictionary']);

Route::get('/referenceDocuments', [ProductdatatemplatesController::class, 'referenceDocuments']);

Route::get('/referenceDocuments/{GUID}', [ProductdatatemplatesController::class, 'referenceDocument']);

Route::get('/groupsOfProperties', [ProductdatatemplatesController::class, 'groupsOfProperties']);

Route::get('/groupsOfProperties/{Id}', [ProductdatatemplatesController::class, 'groupOfProperties']);

Route::get('/{pdtID}', [ProductdatatemplatesController::class, 'productDataTemplate']);
