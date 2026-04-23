<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class PaymentBillUploadedNotification extends Notification implements ShouldQueue {
    use Queueable;

    protected $payment;

    /**
     * Create a new notification instance.
     */
    public function __construct(Payment $payment) {
        $this->payment = $payment;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array {
        $channels = ['database'];

        if ($notifiable->wantsNotification('payment_bill_uploaded', 'email')) {
            $channels[] = 'mail';
        }

        if ($notifiable->wantsNotification('payment_bill_uploaded', 'telegram') && $notifiable->routeNotificationForTelegram()) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage {
        $student = $this->payment->student;
        return (new MailMessage)
            ->subject('💰 Hóa đơn mới cần xác nhận: ' . ($student->full_name ?? 'Sinh viên'))
            ->greeting('Xin chào ' . $notifiable->name . '!')
            ->line('Một hóa đơn thanh toán mới vừa được tải lên hệ thống và đang chờ bạn xác nhận.')
            ->line('**Thông tin thanh toán:**')
            ->line('- Sinh viên: ' . ($student->full_name ?? 'N/A'))
            ->line('- Số tiền: ' . number_format($this->payment->amount, 0, ',', '.') . ' VNĐ')
            ->line('- Người nộp: ' . ($this->payment->collaborator?->full_name ?? 'CTV'))
            ->action('Xem và xác nhận thanh toán', url('/admin/payments/' . $this->payment->id . '/edit'))
            ->line('Vui lòng kiểm tra và tải lên phiếu thu nếu hóa đơn hợp lệ.');
    }

    /**
     * Get the telegram representation of the notification.
     */
    public function toTelegram(object $notifiable) {
        $url = url('/admin/payments/' . $this->payment->id . '/edit');
        $student = $this->payment->student;
        
        $amountLabel = number_format($this->payment->amount, 0, ',', '.') . ' VNĐ';

        return TelegramMessage::create()
            ->to($notifiable->routeNotificationForTelegram())
            ->content("*💰 HÓA ĐƠN MỚI CHỜ XÁC NHẬN*\n\n" .
                "👤 *Sinh viên:* {$student->full_name}\n" .
                "💵 *Số tiền:* `{$amountLabel}`\n" .
                "🤝 *Người giới thiệu:* " . ($this->payment->collaborator?->full_name ?? 'N/A') . "\n" .
                "📅 *Thời gian:* " . now()->format('H:i d/m/Y') . "\n\n" .
                "Vui lòng kiểm tra hóa đơn và cập nhật phiếu thu.")
            ->button('Xác nhận ngay', $url);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array {
        return [
            'payment_id' => $this->payment->id,
            'student_name' => $this->payment->student?->full_name,
            'amount' => $this->payment->amount,
            'type' => 'payment_bill_uploaded',
            'message' => "Hóa đơn mới từ sinh viên {$this->payment->student?->full_name} đang chờ xác nhận.",
        ];
    }
}
