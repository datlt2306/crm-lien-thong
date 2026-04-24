<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\User;
use App\Notifications\StudentRegisteredNotification;
use Illuminate\Support\Facades\Notification;

class TestStudentNotification extends Command {
    protected $signature = 'notify:test-registration {chat_id}';
    protected $description = 'Test thông báo đăng ký sinh viên mới tới Telegram';

    public function handle() {
        $chatId = $this->argument('chat_id');
        $this->info("Đang tìm tài khoản và giả lập sinh viên gửi tới Chat ID: {$chatId}...");

        // Tìm user thật trong DB để có đầy đủ Preferences
        $user = User::where('telegram_chat_id', $chatId)->first();

        if (!$user) {
            $this->error("❌ Không tìm thấy User nào có Chat ID là {$chatId} trong hệ thống.");
            return;
        }

        // Tạo một đối tượng sinh viên giả lập
        $student = new Student();
        $student->full_name = "Nguyễn Văn Test (Mẫu mới)";
        $student->profile_code = "HS2026TEST999";
        $student->dob = "2000-05-20";
        $student->address = "Số 1 Võ Văn Ngân, Thủ Đức, TP.HCM";
        $student->major = "Quản trị kinh doanh";
        $student->program_type = "PART_TIME";
        $student->intake_month = date('n');

        try {
            $user->notifyNow(new StudentRegisteredNotification($student));
            $this->info('✅ Đã gửi thông báo mẫu thành công!');
        } catch (\Exception $e) {
            $this->error('❌ Lỗi: ' . $e->getMessage());
        }
    }
}
