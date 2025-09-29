<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class ViewAllNotifications extends Page {
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected string $view = 'filament.pages.view-all-notifications';

    protected static ?string $navigationLabel = 'Tất cả thông báo';

    protected static ?string $title = 'Tất cả thông báo';

    protected static ?int $navigationSort = 10;

    protected static string|\UnitEnum|null $navigationGroup = 'Thông báo';

    public Collection $notifications;

    public int $unreadCount;

    public function mount(): void {
        $user = Auth::user();
        if ($user) {
            $this->notifications = $user->notifications()
                ->latest()
                ->paginate(20);
            $this->unreadCount = $user->unreadNotifications()->count();
        } else {
            $this->notifications = collect();
            $this->unreadCount = 0;
        }
    }

    public function markAsRead(string $notificationId): void {
        $user = Auth::user();
        if ($user) {
            $notification = $user->notifications()->find($notificationId);
            if ($notification) {
                $notification->markAsRead();
                $this->mount(); // Refresh data
            }
        }
    }

    public function markAllAsRead(): void {
        $user = Auth::user();
        if ($user) {
            $user->unreadNotifications()->update(['read_at' => now()]);
            $this->mount(); // Refresh data
        }
    }

    public function getNotificationsProperty(): Collection {
        return $this->notifications;
    }

    public function getUnreadCountProperty(): int {
        return $this->unreadCount;
    }
}
