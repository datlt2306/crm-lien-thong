<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware(['auth:web'])->group(function () {
    Route::get('/students', [StudentApiController::class, 'index'])->name('api.students.index');
    Route::get('/students/{id}', [StudentApiController::class, 'show'])->name('api.students.show');
});
