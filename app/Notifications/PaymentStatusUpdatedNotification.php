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
        public string $status // VERIFIED or REJECTED
    ) {
    }

    public function via(object $notifiable): array {
        $channels = ['database'];

        if ($notifiable->wantsNotification('payment_bill_uploaded', 'telegram') && $notifiable->routeNotificationForTelegram()) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    public function toTelegram(object $notifiable) {
        $student = $this->payment->student;
        $amountLabel = number_format($this->payment->amount, 0, ',', '.') . ' VNĐ';
        $url = url('/admin/payments');

        if ($this->status === 'VERIFIED') {
            $title = "✅ *HÓA ĐƠN ĐÃ ĐƯỢC DUYỆT*";
            $message = "Chúc mừng! Hóa đơn của sinh viên *{$student?->full_name}* đã được kế toán xác nhận.";
        } else {
            $title = "❌ *HÓA ĐƠN BỊ TỪ CHỐI*";
            $message = "Rất tiếc! Hóa đơn của sinh viên *{$student?->full_name}* không được chấp nhận. Vui lòng kiểm tra lại hình ảnh bill hoặc số tiền.";
        }

        return TelegramMessage::create()
            ->to($notifiable->routeNotificationForTelegram())
            ->content("{$title}\n\n" .
                "{$message}\n\n" .
                "🆔 *Mã hồ sơ:* `{$student?->profile_code}`\n" .
                "👤 *Họ tên:* {$student?->full_name}\n" .
                "📚 *Ngành:* {$student?->major}\n" .
                "🏫 *Hệ:* {$this->payment->program_type}\n" .
                "💰 *Số tiền:* `{$amountLabel}`\n" .
                "🕒 *Thời gian:* " . now()->format('H:i d/m/Y'))
            ->button('Xem chi tiết', $url);
    }

    public function toArray(object $notifiable): array {
        return [
            'payment_id' => $this->payment->id,
            'status' => $this->status,
            'student_name' => $this->payment->student?->full_name,
        ];
    }
}
