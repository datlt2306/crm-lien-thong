<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\StudentApiController;
use App\Http\Controllers\TelegramWebhookController;

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

// Telegram Bot Webhook
Route::post('/telegram/webhook', [TelegramWebhookController::class, 'handle']);

// Temporary debug route - remove after diagnosis
Route::get('/telegram/debug', function () {
    $token = config('services.telegram-bot-api.token');
    $results = ['token_configured' => !empty($token)];

    if ($token) {
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(10)
                ->get("https://api.telegram.org/bot{$token}/getMe");
            $results['getMe'] = $response->json();
            $results['outbound_ok'] = $response->json('ok') === true;
        } catch (\Exception $e) {
            $results['outbound_ok'] = false;
            $results['error'] = $e->getMessage();
        }
    }

    return response()->json($results);
});

