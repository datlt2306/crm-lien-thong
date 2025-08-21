<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicStudentController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});



Route::get('/ref/{ref_id}', [PublicStudentController::class, 'showForm'])->name('public.ref.form');
Route::post('/ref/{ref_id}', [PublicStudentController::class, 'submitForm'])->name('public.ref.submit');
