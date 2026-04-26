<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class PaymentStatusUpdatedNotification extends Notification implements ShouldQueue {
    use Queueable;

    public function __construct(
        public Payment $payment,
        public string $status // verified or rejected
    ) {
    }

    public function via(object $notifiable): array {
        $channels = ['database'];

        if ($notifiable->wantsNotification('payment_bill_uploaded', 'telegram')) {
            $chatId = \App\Models\RefCode::resolveTelegramChatId($this->payment->student?->source_ref ?? null, $notifiable);
            if ($chatId) {
                $channels[] = TelegramChannel::class;
            }
        }

        return $channels;
    }

    public function toTelegram(object $notifiable) {
        $student = $this->payment->student;
        $url = url('/admin/payments');

        // Map tên hệ đào tạo và phí mặc định
        $programLabels = [
            Student::PROGRAM_REGULAR => 'Chính quy',
            Student::PROGRAM_PART_TIME => 'Vừa học vừa làm',
            Student::PROGRAM_DISTANCE => 'Đào tạo từ xa',
        ];
        
        // Phí mặc định từ StudentFeeService (Dùng fallback nếu service không gọi được)
        $defaultFees = [
            Student::PROGRAM_REGULAR => 1750000,
            Student::PROGRAM_PART_TIME => 750000,
            Student::PROGRAM_DISTANCE => 750000,
        ];

        $pType = strtolower((string)$this->payment->program_type);
        $programLabel = $programLabels[$pType] ?? $pType;
        
        // Ưu tiên lấy số tiền đã nộp (nếu > 0), nếu không thì lấy phí mặc định của hệ
        $displayAmount = $this->payment->amount > 0 
            ? $this->payment->amount 
            : ($defaultFees[$pType] ?? 0);

        $amountLabel = number_format($displayAmount, 0, ',', '.') . ' VNĐ';

        if (strtolower($this->status) === 'verified') {
            $title = "✅ *HÓA ĐƠN ĐÃ ĐƯỢC DUYỆT*";
            $message = "Chúc mừng! Hóa đơn của sinh viên *{$student?->full_name}* đã được kế toán xác nhận.";
        } else {
            $title = "❌ *HÓA ĐƠN BỊ TỪ CHỐI*";
            $message = "Rất tiếc! Hóa đơn của sinh viên *{$student?->full_name}* không được chấp nhận. Vui lòng kiểm tra lại hình ảnh bill hoặc số tiền.";
        }

        $chatId = \App\Models\RefCode::resolveTelegramChatId($student?->source_ref ?? null, $notifiable);

        return TelegramMessage::create()
            ->to($chatId)
            ->content("{$title}\n\n" .
                "{$message}\n\n" .
                "🆔 *Mã hồ sơ:* `{$student?->profile_code}`\n" .
                "👤 *Họ tên:* {$student?->full_name}\n" .
                "📚 *Ngành:* {$student?->major}\n" .
                "🏫 *Hệ:* *{$programLabel}*\n" .
                "💰 *Số tiền:* `{$amountLabel}`\n" .
                "🕒 *Thời gian:* " . now()->format('H:i d/m/Y'));
    }

    public function toArray(object $notifiable): array {
        return [
            'payment_id' => $this->payment->id,
            'status' => $this->status,
            'student_name' => $this->payment->student?->full_name,
        ];
    }
}
