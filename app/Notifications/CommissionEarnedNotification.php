<?php

namespace App\Notifications;

use App\Models\CommissionItem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Filament\Notifications\Notification as FilamentNotification;

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

        // Add broadcast for real-time if user wants it
        if ($notifiable->wantsNotification('commission_earned', 'push')) {
            $channels[] = 'broadcast';
        }

        return $channels;
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

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage {
        return new BroadcastMessage([
            'title' => 'Bạn đã nhận được hoa hồng',
            'body' => 'Bạn đã nhận được ' . number_format($this->commissionItem->amount, 0, ',', '.') . ' VNĐ hoa hồng.',
            'icon' => 'heroicon-o-currency-dollar',
            'color' => 'success',
            'data' => [
                'commission_item_id' => $this->commissionItem->id,
                'commission_id' => $this->commissionItem->commission_id,
                'amount' => $this->commissionItem->amount,
                'role' => $this->commissionItem->role,
                'status' => $this->commissionItem->status,
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
        return match ($role) {
            'PRIMARY' => 'CTV chính',
            'SUB' => 'CTV phụ',
            default => $role,
        };
    }

    /**
     * Get status label in Vietnamese.
     */
    private function getStatusLabel(string $status): string {
        return match ($status) {
            'PENDING' => 'Đang chờ',
            'PAYABLE' => 'Có thể thanh toán',
            'PAYMENT_CONFIRMED' => 'Đã xác nhận thanh toán',
            'COMPLETED' => 'Hoàn thành',
            default => $status,
        };
    }
}
