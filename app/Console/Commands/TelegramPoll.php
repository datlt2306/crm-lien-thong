<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\TelegramBotService;
use Illuminate\Support\Facades\Http;

class TelegramPoll extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'telegram:poll';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Chạy Bot Telegram ở chế độ Long Polling để test local';

    /**
     * Execute the console command.
     */
    public function handle(TelegramBotService $botService)
    {
        $token = config('services.telegram-bot-api.token');
        
        if (!$token) {
            $this->error('⚠️ Chưa cấu hình TELEGRAM_BOT_TOKEN trong file .env');
            return;
        }

        $this->info("🚀 Bot đang bắt đầu lắng nghe tin nhắn... (Dùng Ctrl+C để dừng)");
        
        // Hủy Webhook nếu có để không bị tranh chấp tin nhắn
        Http::get("https://api.telegram.org/bot{$token}/deleteWebhook");

        $offset = 0;
        while (true) {
            try {
                $response = Http::timeout(35)->get("https://api.telegram.org/bot{$token}/getUpdates", [
                    'offset' => $offset + 1,
                    'timeout' => 30,
                ]);

                if ($response->successful()) {
                    $updates = $response->json('result');
                    foreach ($updates as $update) {
                        $offset = $update['update_id'];
                        
                        // Xử lý tin nhắn
                        $botService->handleWebhook($update);
                        
                        $from = $update['message']['from']['first_name'] ?? 'Ẩn danh';
                        $text = $update['message']['text'] ?? '[Media/Khác]';
                        $this->info("📩 [{$from}]: {$text}");
                    }
                }
            } catch (\Exception $e) {
                $this->warn("⚠️ Lỗi kết nối: " . $e->getMessage());
            }
            
            usleep(500000); // Nghỉ 0.5s để đỡ tốn tài nguyên
        }
    }
}
