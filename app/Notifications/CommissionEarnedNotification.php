<?php

namespace App\Notifications;

use App\Models\CommissionItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;
use NotificationChannels\Telegram\TelegramChannel;
use NotificationChannels\Telegram\TelegramMessage;

class CommissionEarnedNotification extends Notification implements ShouldQueue {
    use Queueable;

    public function __construct(
        public CommissionItem $commissionItem
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
        if ($notifiable->wantsNotification('commission_earned', 'email')) {
            $channels[] = 'mail';
        }

        // Add telegram if user wants it
        if ($notifiable->wantsNotification('commission_earned', 'telegram')) {
            $student = $this->commissionItem->commission?->student;
            $chatId = \App\Models\RefCode::resolveTelegramChatId($student?->source_ref ?? null, $notifiable);
            if ($chatId) {
                $channels[] = TelegramChannel::class;
            }
        }

        return $channels;
    }

    /**
     * Get the telegram representation of the notification.
     */
    public function toTelegram(object $notifiable) {
        $commission = $this->commissionItem->commission;
        $student = $commission?->student;
        $amountLabel = number_format($this->commissionItem->amount, 0, ',', '.') . ' VNĐ';
        $url = url('/admin/commissions');
        $statusLabel = $this->getStatusLabel($this->commissionItem->status);

        $chatId = \App\Models\RefCode::resolveTelegramChatId($student?->source_ref ?? null, $notifiable);

        return TelegramMessage::create()
            ->to($chatId)
            ->content("💰 *GHI NHẬN HOA HỒNG MỚI (DỰ KIẾN)*\n\n" .
                "Hệ thống vừa ghi nhận một khoản hoa hồng mới dành cho bạn. Khoản này sẽ được chi trả sau khi đối soát trạng thái nhập học của sinh viên.\n\n" .
                "🆔 *Mã hồ sơ:* `{$student?->profile_code}`\n" .
                "👤 *Sinh viên:* {$student?->full_name}\n" .
                "💰 *Số tiền:* `{$amountLabel}`\n" .
                "📝 *Trạng thái:* `{$statusLabel}`\n" .
                "🤝 *Vai trò:* " . $this->getRoleLabel($this->commissionItem->role) . "\n" .
                "🕒 *Thời gian:* " . now()->format('H:i d/m/Y'))
            ->button('Xem ví hoa hồng', $url);
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage {
        return (new MailMessage)
            ->subject('Bạn đã nhận được hoa hồng')
            ->greeting('Xin chào ' . $notifiable->name . '!')
            ->line('Chúc mừng! Bạn đã nhận được hoa hồng từ hệ thống.')
            ->line('Số tiền hoa hồng: ' . number_format($this->commissionItem->amount, 0, ',', '.') . ' VNĐ')
            ->line('Vai trò: ' . $this->getRoleLabel($this->commissionItem->role))
            ->line('Trạng thái: ' . $this->getStatusLabel($this->commissionItem->status))
            ->action('Xem chi tiết', url('/admin/commissions/' . $this->commissionItem->commission_id))
            ->line('Cảm ơn bạn đã đóng góp vào thành công của chúng tôi!');
    }

    // real-time broadcast removed

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array {
        return [
            'title' => 'Bạn đã nhận được hoa hồng',
            'body' => 'Bạn đã nhận được ' . number_format($this->commissionItem->amount, 0, ',', '.') . ' VNĐ hoa hồng.',
            'icon' => 'heroicon-o-currency-dollar',
            'color' => 'success',
            'commission_item_id' => $this->commissionItem->id,
            'commission_id' => $this->commissionItem->commission_id,
            'amount' => $this->commissionItem->amount,
            'role' => $this->commissionItem->role,
            'status' => $this->commissionItem->status,
        ];
    }

    /**
     * Send Filament notification for in-app display.
     */
    public function sendFilamentNotification(object $notifiable): void {
        if ($notifiable->wantsNotification('commission_earned', 'in_app')) {
            FilamentNotification::make()
                ->title('Bạn đã nhận được hoa hồng')
                ->body('Bạn đã nhận được ' . number_format($this->commissionItem->amount, 0, ',', '.') . ' VNĐ hoa hồng.')
                ->success()
                ->icon('heroicon-o-currency-dollar')
                ->sendToDatabase($notifiable);
        }
    }

    /**
     * Get role label in Vietnamese.
     */
    private function getRoleLabel(string $role): string {
        return match (strtolower($role)) {
            'primary' => 'CTV chính',
            'sub' => 'CTV phụ',
            default => $role,
        };
    }

    /**
     * Get status label in Vietnamese.
     */
    private function getStatusLabel(string $status): string {
        return match (strtolower($status)) {
            'pending' => 'Đang chờ',
            'payable' => 'Có thể thanh toán',
            'payment_confirmed' => 'Đã xác nhận thanh toán',
            'paid' => 'Đã thanh toán',
            'cancelled' => 'Đã huỷ',
            'received_confirmed' => 'CTV đã nhận tiền',
            default => $status,
        };
    }
}
