<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class PaymentRejectedNotification extends Notification implements ShouldQueue {
    use Queueable;

    public function __construct(
        public Payment $payment,
        public ?string $reason = null
    ) {
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array {
        $channels = ['database'];

        // Add email if user wants it
        if ($notifiable->wantsNotification('payment_rejected', 'email')) {
            $channels[] = 'mail';
        }

        // real-time and push channels removed

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage {
        $mail = (new MailMessage)
            ->subject('Thanh toán bị từ chối')
            ->greeting('Xin chào ' . $notifiable->name . '!')
            ->line('Rất tiếc, thanh toán của bạn đã bị từ chối.')
            ->line('Số tiền: ' . number_format($this->payment->amount, 0, ',', '.') . ' VNĐ')
            ->line('Loại chương trình: ' . $this->payment->program_type)
            ->line('Trạng thái: Bị từ chối')
            ->action('Xem chi tiết', url('/admin/payments/' . $this->payment->id));

        if ($this->reason) {
            $mail->line('Lý do: ' . $this->reason);
        }

        return $mail->line('Vui lòng liên hệ với chúng tôi nếu bạn có thắc mắc.');
    }

    // real-time broadcast removed

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array {
        return [
            'title' => 'Thanh toán bị từ chối',
            'body' => 'Thanh toán ' . number_format($this->payment->amount, 0, ',', '.') . ' VNĐ đã bị từ chối.',
            'icon' => 'heroicon-o-x-circle',
            'color' => 'danger',
            'payment_id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'program_type' => $this->payment->program_type,
            'reason' => $this->reason,
        ];
    }

    /**
     * Send Filament notification for in-app display.
     */
    public function sendFilamentNotification(object $notifiable): void {
        if ($notifiable->wantsNotification('payment_rejected', 'in_app')) {
            FilamentNotification::make()
                ->title('Thanh toán bị từ chối')
                ->body('Thanh toán ' . number_format($this->payment->amount, 0, ',', '.') . ' VNĐ đã bị từ chối.')
                ->danger()
                ->icon('heroicon-o-x-circle')
                ->sendToDatabase($notifiable);
        }
    }
}
