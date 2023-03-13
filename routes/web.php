<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProductdatatemplatesController;
use App\Http\Controllers\GroupofpropertiesController;
use App\Http\Controllers\PropertiesdatadictionariesController;
use App\Http\Controllers\ReferencedocumentsController;
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

Route::get('/participantes', function () {
    return view('participantes');
})->name('participantes');

Route::get('/privacypolicy', function () {
    return view('privacypolicy');
})->name('privacypolicy');


Route::get('/contact',  function () {
    return view('contact');
});
Route::post('/contact', [ContactController::class, 'store'])->name('contact.store');

Route::get('/dashboard', [ProductdatatemplatesController::class, 'getLatestPDTs'], function () {
    return view('dashboard');
})->middleware(['auth'])->name('dashboard');

Route::get('/pdtsdownload/{pdtID}', [GroupofpropertiesController::class, 'getGroupOfProperties'])
    ->middleware(['auth'])->name('pdtsdownload');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});
Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto'])
    ->middleware(['auth'])->name('profile.deletePhoto');
Route::post('/profile/photo', [ProfileController::class, 'updatePhoto'])
    ->middleware(['auth'])->name('profile.updatePhoto');
Route::post('/updateSubscription', [ProfileController::class, 'updateSubscription'])
    ->middleware(['auth'])->name('profile.updateSubscription');

require __DIR__ . '/auth.php';

Route::get('/pdtssurvey/{pdtID}', [GroupofpropertiesController::class, 'getGroupOfProperties2'])
    ->middleware(['auth'])->name('pdtssurvey');
Route::post('/pdtssurvey/saveAnswers', [GroupofpropertiesController::class, 'saveAnswers'])
    ->middleware(['auth'])->name('saveAnswers');
Route::post('/pdtssurvey/{pdtID}', [GroupofpropertiesController::class, 'store'])
    ->middleware(['auth']);
Route::post('/pdtssurvey/store', [GroupofpropertiesController::class, 'store'])
    ->middleware(['auth'])->name('pdtssurveystore');

Route::delete('/deletefeedback', [GroupofpropertiesController::class, 'destroyfeedback']);



Route::get(
    '/datadictionaryview/{propID}{propV}{propR}',
    [PropertiesdatadictionariesController::class, 'getPropertyDataDictionary']
)->middleware(['auth'])->name('datadictionaryview');

Route::get(
    '/referencedocumentview/{rdGUID}',
    [ReferencedocumentsController::class, 'getReferenceDocument']
)->middleware(['auth'])->name('referencedocumentview');

Route::get('/admin', [UserController::class, 'index'])->middleware(['auth'])->name('admin');

Route::post('/admin/update', [UserController::class, 'updateUsers'])->middleware(['auth'])->name('update.users');
