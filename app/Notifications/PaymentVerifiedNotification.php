<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class PaymentVerifiedNotification extends Notification implements ShouldQueue {
    use Queueable;

    public function __construct(
        public Payment $payment
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
        if ($notifiable->wantsNotification('payment_verified', 'email')) {
            $channels[] = 'mail';
        }

        // Add broadcast for real-time if user wants it
        if ($notifiable->wantsNotification('payment_verified', 'push')) {
            $channels[] = 'broadcast';
            $channels[] = 'firebase';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage {
        return (new MailMessage)
            ->subject('Thanh toán đã được xác minh')
            ->greeting('Xin chào ' . $notifiable->name . '!')
            ->line('Thanh toán của bạn đã được xác minh thành công.')
            ->line('Số tiền: ' . number_format($this->payment->amount, 0, ',', '.') . ' VNĐ')
            ->line('Loại chương trình: ' . $this->payment->program_type)
            ->line('Trạng thái: Đã xác minh')
            ->action('Xem chi tiết', url('/admin/payments/' . $this->payment->id))
            ->line('Cảm ơn bạn đã sử dụng dịch vụ của chúng tôi!');
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage {
        return new BroadcastMessage([
            'title' => 'Thanh toán đã được xác minh',
            'body' => 'Thanh toán ' . number_format($this->payment->amount, 0, ',', '.') . ' VNĐ đã được xác minh thành công.',
            'icon' => 'heroicon-o-check-circle',
            'color' => 'success',
            'data' => [
                'payment_id' => $this->payment->id,
                'amount' => $this->payment->amount,
                'program_type' => $this->payment->program_type,
            ]
        ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array {
        return [
            'title' => 'Thanh toán đã được xác minh',
            'body' => 'Thanh toán ' . number_format($this->payment->amount, 0, ',', '.') . ' VNĐ đã được xác minh thành công.',
            'icon' => 'heroicon-o-check-circle',
            'color' => 'success',
            'payment_id' => $this->payment->id,
            'amount' => $this->payment->amount,
            'program_type' => $this->payment->program_type,
        ];
    }

    /**
     * Get the Firebase representation of the notification.
     */
    public function toFirebase(object $notifiable): array {
        return [
            'title' => 'Thanh toán đã được xác minh',
            'body' => 'Thanh toán ' . number_format($this->payment->amount, 0, ',', '.') . ' VNĐ đã được xác minh thành công.',
            'icon' => 'heroicon-o-check-circle',
            'color' => 'success',
            'data' => [
                'payment_id' => $this->payment->id,
                'amount' => $this->payment->amount,
                'program_type' => $this->payment->program_type,
                'type' => 'payment_verified',
            ]
        ];
    }

    /**
     * Send Filament notification for in-app display.
     */
    public function sendFilamentNotification(object $notifiable): void {
        if ($notifiable->wantsNotification('payment_verified', 'in_app')) {
            FilamentNotification::make()
                ->title('Thanh toán đã được xác minh')
                ->body('Thanh toán ' . number_format($this->payment->amount, 0, ',', '.') . ' VNĐ đã được xác minh thành công.')
                ->success()
                ->icon('heroicon-o-check-circle')
                ->sendToDatabase($notifiable);
        }
    }
}
