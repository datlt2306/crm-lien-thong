<?php

namespace App\Notifications;

use App\Models\CollaboratorRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CollaboratorRegistrationRejectedNotification extends Notification implements ShouldQueue {
    use Queueable;

    protected CollaboratorRegistration $registration;

    /**
     * Create a new notification instance.
     */
    public function __construct(CollaboratorRegistration $registration) {
        $this->registration = $registration;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage {
        return (new MailMessage)
            ->subject('Đăng ký cộng tác viên không được duyệt')
            ->greeting('Xin chào ' . $this->registration->full_name . '!')
            ->line('Chúng tôi rất tiếc thông báo rằng đăng ký cộng tác viên của bạn không được duyệt.')
            ->line('Lý do từ chối: ' . ($this->registration->rejection_reason ?? 'Không được cung cấp'))
            ->line('Nếu bạn có bất kỳ thắc mắc nào, vui lòng liên hệ với chúng tôi để được hỗ trợ.')
            ->action('Liên hệ hỗ trợ', url('/contact'))
            ->line('Cảm ơn bạn đã quan tâm đến chương trình cộng tác viên của chúng tôi!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array {
        return [
            'title' => 'Đăng ký cộng tác viên không được duyệt',
            'message' => "Đăng ký của {$this->registration->full_name} không được duyệt. Lý do: " . ($this->registration->rejection_reason ?? 'Không được cung cấp'),
            'type' => 'error',
            'registration_id' => $this->registration->id,
            'rejection_reason' => $this->registration->rejection_reason,
        ];
    }
}
