<?php

namespace App\Notifications;

use App\Models\Payment;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SimplePaymentVerifiedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Payment $payment
    ) {}

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
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
}
