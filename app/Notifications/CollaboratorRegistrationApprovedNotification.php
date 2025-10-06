<?php

namespace App\Notifications;

use App\Models\CollaboratorRegistration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CollaboratorRegistrationApprovedNotification extends Notification implements ShouldQueue {
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
            ->subject('Đăng ký cộng tác viên đã được duyệt')
            ->greeting('Xin chào ' . $this->registration->full_name . '!')
            ->line('Chúc mừng! Đăng ký cộng tác viên của bạn đã được duyệt thành công.')
            ->line('Thông tin cộng tác viên:')
            ->line('- Họ tên: ' . $this->registration->full_name)
            ->line('- Số điện thoại: ' . $this->registration->phone)
            ->line('- Mã REF: ' . $this->registration->ref_id)
            ->line('- Tổ chức: ' . $this->registration->organization->name)
            ->line('Bạn đã trở thành cộng tác viên chính thức và có thể bắt đầu hoạt động.')
            ->action('Đăng nhập hệ thống', url('/admin'))
            ->line('Cảm ơn bạn đã tham gia!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array {
        return [
            'title' => 'Đăng ký cộng tác viên đã được duyệt',
            'message' => "Chúc mừng {$this->registration->full_name}! Đăng ký của bạn đã được duyệt thành công.",
            'type' => 'success',
            'registration_id' => $this->registration->id,
            'ref_id' => $this->registration->ref_id,
        ];
    }
}
