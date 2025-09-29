<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Collection;

class UserNotificationsWidget extends Widget
{
    protected string $view = 'filament.widgets.user-notifications-widget';
    
    protected int | string | array $columnSpan = 'full';
    
    public Collection $notifications;
    
    public function mount(): void
    {
        $user = Auth::user();
        if ($user) {
            $this->notifications = $user->notifications()
                ->latest()
                ->limit(5)
                ->get();
        } else {
            $this->notifications = collect();
        }
    }
    
    public function getUnreadCount(): int
    {
        $user = Auth::user();
        if (!$user) {
            return 0;
        }
        
        return $user->unreadNotifications()->count();
    }
    
    public function markAsRead(string $notificationId): void
    {
        $user = Auth::user();
        if ($user) {
            $notification = $user->notifications()->find($notificationId);
            if ($notification) {
                $notification->markAsRead();
                $this->notifications = $user->notifications()
                    ->latest()
                    ->limit(5)
                    ->get();
            }
        }
    }
    
    public function markAllAsRead(): void
    {
        $user = Auth::user();
        if ($user) {
            $user->unreadNotifications()->update(['read_at' => now()]);
            $this->notifications = $user->notifications()
                ->latest()
                ->limit(5)
                ->get();
        }
    }
}
