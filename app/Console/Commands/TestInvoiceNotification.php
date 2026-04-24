<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Payment;
use App\Models\Student;
use App\Models\User;
use App\Notifications\PaymentBillUploadedNotification;

class TestInvoiceNotification extends Command {
    protected $signature = 'notify:test-invoice {chat_id}';
    protected $description = 'Test thông báo hóa đơn mới tới Telegram';

    public function handle() {
        $chatId = $this->argument('chat_id');
        $this->info("Đang giả lập hóa đơn và gửi thông báo tới Chat ID: {$chatId}...");

        // Tìm user thật trong DB
        $user = User::where('telegram_chat_id', $chatId)->first();
        if (!$user) {
            $this->error("❌ Không tìm thấy User nào có Chat ID là {$chatId} trong hệ thống.");
            return;
        }

        // Tạo đối tượng sinh viên giả lập
        $student = new Student();
        $student->full_name = "Trần Thị Hóa Đơn";
        $student->profile_code = "HS2026BILL007";
        $student->dob = "1999-12-12";
        $student->address = "456 Lê Lợi, Quận 1, TP.HCM";
        $student->major = "Kế toán";
        $student->program_type = "REGULAR";
        $student->intake_month = 6;

        // Tạo đối tượng thanh toán giả lập
        $payment = new Payment();
        $payment->amount = 5000000;
        $payment->setRelation('student', $student);

        try {
            $user->notifyNow(new PaymentBillUploadedNotification($payment));
            $this->info('✅ Đã gửi thông báo hóa đơn mẫu thành công!');
        } catch (\Exception $e) {
            $this->error('❌ Lỗi: ' . $e->getMessage());
        }
    }
}
