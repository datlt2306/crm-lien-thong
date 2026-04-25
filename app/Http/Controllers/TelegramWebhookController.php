<?php

namespace App\Http\Controllers;

use App\Services\TelegramBotService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TelegramWebhookController extends Controller
{
    protected $telegramService;

    public function __construct(TelegramBotService $telegramService)
    {
        $this->telegramService = $telegramService;
    }

    /**
     * Handle Telegram Webhook
     */
    public function handle(Request $request)
    {
        $data = $request->all();

        // Log để debug nếu cần
        Log::info('Telegram Webhook received', $data);

        $this->telegramService->handleWebhook($data);

        return response()->json(['status' => 'ok']);
    }
}
