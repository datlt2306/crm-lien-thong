<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CommissionItem;
use App\Models\Commission;
use App\Models\Student;
use App\Models\User;
use App\Notifications\CommissionEarnedNotification;

class TestCommissionNotification extends Command {
    protected $signature = 'notify:test-commission {chat_id}';
    protected $description = 'Test thông báo nhận hoa hồng tới Telegram';

    public function handle() {
        $chatId = $this->argument('chat_id');
        $this->info("Đang giả lập hoa hồng và gửi thông báo tới Chat ID: {$chatId}...");

        $user = User::where('telegram_chat_id', $chatId)->first();
        if (!$user) {
            $this->error("❌ Không tìm thấy User nào có Chat ID là {$chatId}.");
            return;
        }

        // Giả lập dữ liệu
        $student = new Student();
        $student->full_name = "Lê Thị May Mắn";
        $student->profile_code = "HS2026LUCKY";

        $commission = new Commission();
        $commission->setRelation('student', $student);

        $item = new CommissionItem();
        $item->amount = 1500000;
        $item->role = 'PRIMARY';
        $item->status = 'PENDING';
        $item->setRelation('commission', $commission);

        try {
            $user->notifyNow(new CommissionEarnedNotification($item));
            $this->info('✅ Đã gửi thông báo hoa hồng mẫu thành công!');
        } catch (\Exception $e) {
            $this->error('❌ Lỗi: ' . $e->getMessage());
        }
    }
}
