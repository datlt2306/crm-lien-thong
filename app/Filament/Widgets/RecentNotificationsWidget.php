<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class RecentNotificationsWidget extends Widget {
    protected string $view = 'filament.widgets.recent-notifications-widget';

    protected int | string | array $columnSpan = 'full';

    public Collection $notifications;

    public function mount(): void {
        $user = Auth::user();
        if ($user) {
            $this->notifications = $user->notifications()
                ->latest()
                ->limit(10)
                ->get();
        } else {
            $this->notifications = collect();
        }
    }

    public function markAsRead(string $notificationId): void {
        $user = Auth::user();
        if ($user) {
            $notification = $user->notifications()->find($notificationId);
            if ($notification) {
                $notification->markAsRead();
                $this->notifications = $user->notifications()
                    ->latest()
                    ->limit(10)
                    ->get();
            }
        }
    }

    public function markAllAsRead(): void {
        $user = Auth::user();
        if ($user) {
            $user->unreadNotifications()->update(['read_at' => now()]);
            $this->notifications = $user->notifications()
                ->latest()
                ->limit(10)
                ->get();
        }
    }
}
