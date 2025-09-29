<?php

namespace App\Filament\Widgets;

use App\Models\PushToken;
use App\Models\NotificationPreference;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class NotificationsWidget extends BaseWidget {
    protected function getStats(): array {
        $user = Auth::user();

        return [
            Stat::make('Push Tokens Hoạt động', PushToken::active()->count())
                ->description('Tổng số thiết bị đăng ký nhận thông báo')
                ->descriptionIcon('heroicon-m-device-phone-mobile')
                ->color('success'),

            Stat::make('Người dùng có cài đặt', NotificationPreference::count())
                ->description('Số người dùng đã cấu hình thông báo')
                ->descriptionIcon('heroicon-m-users')
                ->color('info'),

            Stat::make('Thông báo chưa đọc', $this->getUnreadNotificationsCount())
                ->description('Thông báo chưa đọc của bạn')
                ->descriptionIcon('heroicon-m-bell')
                ->color($this->getUnreadNotificationsCount() > 0 ? 'warning' : 'gray'),

            Stat::make('Thiết bị Web', PushToken::active()->where('platform', 'web')->count())
                ->description('Thiết bị web đang hoạt động')
                ->descriptionIcon('heroicon-m-computer-desktop')
                ->color('primary'),
        ];
    }

    private function getUnreadNotificationsCount(): int {
        $user = Auth::user();
        if (!$user) {
            return 0;
        }

        return $user->unreadNotifications()->count();
    }
}
