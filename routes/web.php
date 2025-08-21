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

// Test route để kiểm tra ViewMyWallet
Route::get('/test-wallet', function () {
    $user = \App\Models\User::where('email', 'dat.le@example.com')->first();
    if ($user) {
        \Illuminate\Support\Facades\Auth::login($user);
        
        // Test trực tiếp ViewMyWallet
        $page = new \App\Filament\Resources\WalletResource\Pages\ViewMyWallet();
        $page->mount();
        
        return response()->json([
            'user' => $user->name,
            'data' => $page->data,
            'content' => $page->getContent()
        ]);
    }
    return 'User not found';
});

Route::get('/ref/{ref_id}', [PublicStudentController::class, 'showForm'])->name('public.ref.form');
Route::post('/ref/{ref_id}', [PublicStudentController::class, 'submitForm'])->name('public.ref.submit');
