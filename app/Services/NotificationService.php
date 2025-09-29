<?php

namespace App\Services;

use App\Models\User;
use App\Models\Payment;
use App\Models\CommissionItem;
use App\Notifications\PaymentVerifiedNotification;
use App\Notifications\PaymentRejectedNotification;
use App\Notifications\CommissionEarnedNotification;
use App\Notifications\QuotaWarningNotification;
use App\Broadcasting\NotificationChannel;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Log;

class NotificationService {
    /**
     * Send payment verified notification to relevant users.
     */
    public function notifyPaymentVerified(Payment $payment): void {
        $users = $this->getUsersForPaymentNotification($payment);

        foreach ($users as $user) {
            if ($user->wantsNotification('payment_verified')) {
                $user->notify(new PaymentVerifiedNotification($payment));
                $this->sendFilamentNotification($user, 'payment_verified', $payment);
                $this->broadcastNotification($user, [
                    'title' => 'Thanh toán đã được xác minh',
                    'body' => 'Thanh toán ' . number_format($payment->amount, 0, ',', '.') . ' VNĐ đã được xác minh thành công.',
                    'icon' => 'heroicon-o-check-circle',
                    'color' => 'success',
                    'data' => [
                        'payment_id' => $payment->id,
                        'type' => 'payment_verified',
                    ]
                ]);
            }
        }
    }

    /**
     * Send payment rejected notification to relevant users.
     */
    public function notifyPaymentRejected(Payment $payment, ?string $reason = null): void {
        $users = $this->getUsersForPaymentNotification($payment);

        foreach ($users as $user) {
            if ($user->wantsNotification('payment_rejected')) {
                $user->notify(new PaymentRejectedNotification($payment, $reason));
                $this->sendFilamentNotification($user, 'payment_rejected', $payment);
                $this->broadcastNotification($user, [
                    'title' => 'Thanh toán bị từ chối',
                    'body' => 'Thanh toán ' . number_format($payment->amount, 0, ',', '.') . ' VNĐ đã bị từ chối.',
                    'icon' => 'heroicon-o-x-circle',
                    'color' => 'danger',
                    'data' => [
                        'payment_id' => $payment->id,
                        'type' => 'payment_rejected',
                        'reason' => $reason,
                    ]
                ]);
            }
        }
    }

    /**
     * Send commission earned notification to collaborator.
     */
    public function notifyCommissionEarned(CommissionItem $commissionItem): void {
        $collaborator = $commissionItem->collaborator;
        if (!$collaborator) {
            return;
        }

        // Find user associated with this collaborator
        $user = User::where('email', $collaborator->email)->first();
        if (!$user) {
            return;
        }

        if ($user->wantsNotification('commission_earned')) {
            $user->notify(new CommissionEarnedNotification($commissionItem));
            $this->sendFilamentNotification($user, 'commission_earned', $commissionItem);
            $this->broadcastNotification($user, [
                'title' => 'Bạn đã nhận được hoa hồng',
                'body' => 'Bạn đã nhận được ' . number_format($commissionItem->amount, 0, ',', '.') . ' VNĐ hoa hồng.',
                'icon' => 'heroicon-o-currency-dollar',
                'color' => 'success',
                'data' => [
                    'commission_item_id' => $commissionItem->id,
                    'type' => 'commission_earned',
                ]
            ]);
        }
    }

    /**
     * Send quota warning notification to relevant users.
     */
    public function notifyQuotaWarning(string $majorName, int $remainingQuota, int $totalQuota, ?int $organizationId = null): void {
        $users = $this->getUsersForQuotaWarning($organizationId);

        foreach ($users as $user) {
            if ($user->wantsNotification('quota_warning')) {
                $user->notify(new QuotaWarningNotification($majorName, $remainingQuota, $totalQuota, $organizationId));
                $this->sendFilamentNotification($user, 'quota_warning', null, [
                    'major_name' => $majorName,
                    'remaining_quota' => $remainingQuota,
                    'total_quota' => $totalQuota,
                ]);
                $this->broadcastNotification($user, [
                    'title' => 'Cảnh báo: Chỉ tiêu sắp hết',
                    'body' => 'Ngành ' . $majorName . ' chỉ còn ' . $remainingQuota . ' chỉ tiêu.',
                    'icon' => 'heroicon-o-exclamation-triangle',
                    'color' => 'warning',
                    'data' => [
                        'major_name' => $majorName,
                        'remaining_quota' => $remainingQuota,
                        'total_quota' => $totalQuota,
                        'type' => 'quota_warning',
                    ]
                ]);
            }
        }
    }

    /**
     * Get users who should be notified about payment events.
     */
    private function getUsersForPaymentNotification(Payment $payment): array {
        $users = [];

        // Add collaborator who submitted the payment
        if ($payment->primaryCollaborator) {
            $user = User::where('email', $payment->primaryCollaborator->email)->first();
            if ($user) {
                $users[] = $user;
            }
        }

        if ($payment->subCollaborator) {
            $user = User::where('email', $payment->subCollaborator->email)->first();
            if ($user) {
                $users[] = $user;
            }
        }

        // Add organization owner
        if ($payment->organization && $payment->organization->owner) {
            $users[] = $payment->organization->owner;
        }

        // Add super admins
        $superAdmins = User::where('role', 'super_admin')->get();
        foreach ($superAdmins as $admin) {
            $users[] = $admin;
        }
        
        // Add accountants
        $accountants = User::where('role', 'kế toán')->get();
        foreach ($accountants as $accountant) {
            $users[] = $accountant;
        }
        
        return array_unique($users, SORT_REGULAR);
    }

    /**
     * Get users who should be notified about quota warnings.
     */
    private function getUsersForQuotaWarning(?int $organizationId = null): array {
        $users = [];

        // Add super admins
        $superAdmins = User::where('role', 'super_admin')->get();
        foreach ($superAdmins as $admin) {
            $users[] = $admin;
        }

        // Add organization owner if specified
        if ($organizationId) {
            $organization = \App\Models\Organization::find($organizationId);
            if ($organization && $organization->owner) {
                $users[] = $organization->owner;
            }
        }

        return array_unique($users, SORT_REGULAR);
    }

    /**
     * Send Filament notification for in-app display.
     */
    private function sendFilamentNotification(User $user, string $type, $record = null, array $extraData = []): void {
        if (!$user->wantsNotification($type, 'in_app')) {
            return;
        }

        $notification = \Filament\Notifications\Notification::make();

        switch ($type) {
            case 'payment_verified':
                $notification->title('Thanh toán đã được xác minh')
                    ->body('Thanh toán ' . number_format($record->amount, 0, ',', '.') . ' VNĐ đã được xác minh thành công.')
                    ->success()
                    ->icon('heroicon-o-check-circle');
                break;

            case 'payment_rejected':
                $notification->title('Thanh toán bị từ chối')
                    ->body('Thanh toán ' . number_format($record->amount, 0, ',', '.') . ' VNĐ đã bị từ chối.')
                    ->danger()
                    ->icon('heroicon-o-x-circle');
                break;

            case 'commission_earned':
                $notification->title('Bạn đã nhận được hoa hồng')
                    ->body('Bạn đã nhận được ' . number_format($record->amount, 0, ',', '.') . ' VNĐ hoa hồng.')
                    ->success()
                    ->icon('heroicon-o-currency-dollar');
                break;

            case 'quota_warning':
                $notification->title('Cảnh báo: Chỉ tiêu sắp hết')
                    ->body('Ngành ' . $extraData['major_name'] . ' chỉ còn ' . $extraData['remaining_quota'] . ' chỉ tiêu.')
                    ->warning()
                    ->icon('heroicon-o-exclamation-triangle');
                break;
        }

        $notification->sendToDatabase($user);
    }

    /**
     * Broadcast notification for real-time updates.
     */
    private function broadcastNotification(User $user, array $notification): void {
        if (!$user->wantsNotification('system_updates', 'push')) {
            return;
        }

        try {
            Broadcast::event(new NotificationChannel($user->id, $notification));
        } catch (\Exception $e) {
            Log::error('Failed to broadcast notification: ' . $e->getMessage());
        }
    }
}
