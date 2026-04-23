<?php

namespace App\Notifications;

use App\Models\Student;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class StudentRegisteredNotification extends Notification implements ShouldQueue {
    use Queueable;

    protected $student;

    /**
     * Create a new notification instance.
     */
    public function __construct(Student $student) {
        $this->student = $student;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array {
        $channels = ['database'];

        if ($notifiable->wantsNotification('student_registered', 'email')) {
            $channels[] = 'mail';
        }

        if ($notifiable->wantsNotification('student_registered', 'telegram') && $notifiable->routeNotificationForTelegram()) {
            $channels[] = TelegramChannel::class;
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage {
        return (new MailMessage)
            ->subject('🎓 Sinh viên mới đăng ký: ' . $this->student->full_name)
            ->greeting('Xin chào ' . $notifiable->name . '!')
            ->line('Hệ thống vừa ghi nhận một sinh viên mới đăng ký thông qua liên kết giới thiệu của bạn.')
            ->line('**Thông tin sinh viên:**')
            ->line('- Họ tên: ' . $this->student->full_name)
            ->line('- Mã hồ sơ: ' . $this->student->profile_code)
            ->line('- Ngành học: ' . $this->student->major)
            ->line('- Hệ đào tạo: ' . $this->student->program_type_label)
            ->action('Xem chi tiết hồ sơ', url('/admin/students/' . $this->student->id))
            ->line('Chúc bạn một ngày làm việc hiệu quả!');
    }

    /**
     * Get the telegram representation of the notification.
     */
    public function toTelegram(object $notifiable) {
        $url = url('/admin/students/' . $this->student->id);
        
        $collaboratorName = $this->student->collaborator?->full_name ?? 'Trực tiếp';

        return TelegramMessage::create()
            ->to($notifiable->routeNotificationForTelegram())
            ->content("*🎓 CÓ SINH VIÊN ĐĂNG KÝ MỚI*\n\n" .
                "👤 *Họ tên:* {$this->student->full_name}\n" .
                "🆔 *Mã hồ sơ:* `{$this->student->profile_code}`\n" .
                "📚 *Ngành:* {$this->student->major}\n" .
                "🏫 *Hệ:* {$this->student->program_type_label}\n" .
                "🤝 *Người giới thiệu:* {$collaboratorName}\n" .
                "📅 *Thời gian:* " . now()->format('H:i d/m/Y'))
            ->button('Xem hồ sơ chi tiết', $url);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array {
        return [
            'student_id' => $this->student->id,
            'student_name' => $this->student->full_name,
            'profile_code' => $this->student->profile_code,
            'type' => 'student_registered',
            'message' => "Sinh viên {$this->student->full_name} đã đăng ký thành công.",
        ];
    }
}
