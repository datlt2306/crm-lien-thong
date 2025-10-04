<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

class QuotaWarningNotification extends Notification implements ShouldQueue {
    use Queueable;

    public function __construct(
        public string $majorName,
        public int $remainingQuota,
        public int $totalQuota,
        public ?int $organizationId = null
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
        if ($notifiable->wantsNotification('quota_warning', 'email')) {
            $channels[] = 'mail';
        }

        // real-time and push channels removed

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage {
        $percentage = round(($this->remainingQuota / $this->totalQuota) * 100, 1);

        return (new MailMessage)
            ->subject('Cảnh báo: Chỉ tiêu sắp hết')
            ->greeting('Xin chào ' . $notifiable->name . '!')
            ->line('Chúng tôi muốn thông báo cho bạn về tình trạng chỉ tiêu của ngành học.')
            ->line('Ngành học: ' . $this->majorName)
            ->line('Chỉ tiêu còn lại: ' . $this->remainingQuota . ' / ' . $this->totalQuota . ' (' . $percentage . '%)')
            ->line('Hãy cân nhắc việc tuyển sinh để tối ưu hóa cơ hội.')
            ->action('Xem chi tiết chỉ tiêu', url('/admin/majors'))
            ->line('Vui lòng liên hệ với chúng tôi nếu bạn cần hỗ trợ thêm.');
    }

    // real-time broadcast removed

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array {
        $percentage = round(($this->remainingQuota / $this->totalQuota) * 100, 1);

        return [
            'title' => 'Cảnh báo: Chỉ tiêu sắp hết',
            'body' => 'Ngành ' . $this->majorName . ' chỉ còn ' . $this->remainingQuota . ' chỉ tiêu (' . $percentage . '%)',
            'icon' => 'heroicon-o-exclamation-triangle',
            'color' => 'warning',
            'major_name' => $this->majorName,
            'remaining_quota' => $this->remainingQuota,
            'total_quota' => $this->totalQuota,
            'percentage' => $percentage,
            'organization_id' => $this->organizationId,
        ];
    }

    /**
     * Send Filament notification for in-app display.
     */
    public function sendFilamentNotification(object $notifiable): void {
        if ($notifiable->wantsNotification('quota_warning', 'in_app')) {
            $percentage = round(($this->remainingQuota / $this->totalQuota) * 100, 1);

            FilamentNotification::make()
                ->title('Cảnh báo: Chỉ tiêu sắp hết')
                ->body('Ngành ' . $this->majorName . ' chỉ còn ' . $this->remainingQuota . ' chỉ tiêu (' . $percentage . '%)')
                ->warning()
                ->icon('heroicon-o-exclamation-triangle')
                ->sendToDatabase($notifiable);
        }
    }
}
