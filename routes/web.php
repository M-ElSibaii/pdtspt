<?php

use App\Http\Controllers\ContactController;
use App\Http\Controllers\CommentsController;
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

require __DIR__ . '/auth.php';

Route::get('/pdtssurvey/{pdtID}', [GroupofpropertiesController::class, 'getGroupOfProperties2'])
    ->middleware(['auth'])->name('pdtssurvey');

Route::get('/fetchfeedback', [GroupofpropertiesController::class, 'fetchfeedback']);

Route::delete('/deletefeedback/{commentId}', [StudentController::class, 'destroyfeedback']);

Route::post('/pdtssurvey/{pdtID}', [GroupofpropertiesController::class, 'store'])
    ->middleware(['auth'])->name('pdtssurveystore');

Route::post('/pdtssurvey/saveAnswers', [GroupofpropertiesController::class, 'saveAnswers'])
    ->middleware(['auth'])->name('saveAnswers');

Route::get(
    '/datadictionaryview/{propID}{propV}{propR}',
    [PropertiesdatadictionariesController::class, 'getPropertyDataDictionary']
)->middleware(['auth'])->name('datadictionaryview');

Route::get(
    '/referencedocumentview/{rdGUID}',
    [ReferencedocumentsController::class, 'getReferenceDocument']
)->middleware(['auth'])->name('referencedocumentview');
