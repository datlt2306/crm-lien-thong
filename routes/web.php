<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicStudentController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/ref/{ref_id}', [PublicStudentController::class, 'showForm'])->name('public.ref.form');
Route::post('/ref/{ref_id}', [PublicStudentController::class, 'submitForm'])->name('public.ref.submit');
