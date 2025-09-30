<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\CommissionItem;
use App\Services\DashboardCacheService;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Carbon\CarbonImmutable;
use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;

class RealtimeNotificationsWidget extends Widget {
    use WithDashboardFilters;
    protected string $view = 'filament.widgets.realtime-notifications';
    protected ?string $heading = 'Thông báo real-time';

    public function getHeading(): string {
        return $this->heading ?? 'Thông báo real-time';
    }
    protected int|string|array $columnSpan = 'full';
    protected ?string $pollingInterval = '30s';

    public array $notifications = [];

    public function mount(): void {
        $this->loadNotifications();
    }

    public function loadNotifications(): void {
        $user = Auth::user();
        $role = $user?->role;

        $notifications = [];

        // Sử dụng cache để giảm database queries
        $cacheKey = "notifications:{$user->id}:{$role}";
        $cachedNotifications = cache()->get($cacheKey);

        if ($cachedNotifications && now()->diffInMinutes($cachedNotifications['timestamp']) < 5) {
            $this->notifications = $cachedNotifications['data'];
            return;
        }

        // Thông báo cho admin và kế toán
        if (in_array($role, ['super_admin', 'admin', 'kế toán'])) {
            // Payment mới chờ xác minh - tối ưu query
            $newPayments = Payment::where('status', 'pending')
                ->where('created_at', '>=', now()->subHours(24))
                ->count();

            if ($newPayments > 0) {
                $notifications[] = [
                    'type' => 'payment',
                    'title' => 'Payment mới chờ xác minh',
                    'message' => "Có {$newPayments} payment mới cần xác minh",
                    'icon' => 'heroicon-o-credit-card',
                    'color' => 'warning',
                    'time' => now()->format('H:i'),
                ];
            }

            // Payment đã được xác minh - tối ưu query
            $verifiedPayments = Payment::where('status', 'verified')
                ->where('updated_at', '>=', now()->subHours(24))
                ->count();

            if ($verifiedPayments > 0) {
                $notifications[] = [
                    'type' => 'payment_verified',
                    'title' => 'Payment đã được xác minh',
                    'message' => "Có {$verifiedPayments} payment đã được xác minh trong 24h qua",
                    'icon' => 'heroicon-o-check-circle',
                    'color' => 'success',
                    'time' => now()->format('H:i'),
                ];
            }
        }

        // Thông báo cho CTV
        if ($role === 'ctv') {
            $userId = $user->id;

            // Commission mới - tối ưu query
            $newCommissions = CommissionItem::where('collaborator_id', $userId)
                ->where('status', 'pending')
                ->where('created_at', '>=', now()->subHours(24))
                ->sum('amount');

            if ($newCommissions > 0) {
                $notifications[] = [
                    'type' => 'commission',
                    'title' => 'Hoa hồng mới',
                    'message' => "Bạn có " . number_format($newCommissions) . " VND hoa hồng mới",
                    'icon' => 'heroicon-o-banknotes',
                    'color' => 'success',
                    'time' => now()->format('H:i'),
                ];
            }

            // Học viên mới - tối ưu query
            $newStudents = \App\Models\Student::whereHas('payments', function ($query) use ($userId) {
                $query->where('collaborator_id', $userId);
            })->where('created_at', '>=', now()->subHours(24))->count();

            if ($newStudents > 0) {
                $notifications[] = [
                    'type' => 'student',
                    'title' => 'Học viên mới',
                    'message' => "Bạn có {$newStudents} học viên mới đăng ký",
                    'icon' => 'heroicon-o-user-plus',
                    'color' => 'info',
                    'time' => now()->format('H:i'),
                ];
            }
        }

        // Thông báo cho kế toán
        if ($role === 'kế toán') {
            // Phiếu thu chờ upload - tối ưu query
            $pendingReceipts = Payment::where('status', 'verified')
                ->whereNull('receipt_path')
                ->count();

            if ($pendingReceipts > 0) {
                $notifications[] = [
                    'type' => 'receipt',
                    'title' => 'Phiếu thu chờ upload',
                    'message' => "Có {$pendingReceipts} phiếu thu cần upload",
                    'icon' => 'heroicon-o-document',
                    'color' => 'warning',
                    'time' => now()->format('H:i'),
                ];
            }
        }

        // Cache notifications trong 5 phút
        cache()->put($cacheKey, [
            'data' => $notifications,
            'timestamp' => now()
        ], 300);

        $this->notifications = $notifications;
    }

    public function markAsRead(string $type): void {
        // Logic để đánh dấu thông báo đã đọc
        // Có thể lưu vào database hoặc session
    }

    public function clearAll(): void {
        $this->notifications = [];
    }
}
