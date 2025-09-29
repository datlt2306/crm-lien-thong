<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\User;

class ViewAllNotifications extends Page {
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell';

    protected string $view = 'filament.pages.view-all-notifications';

    protected static ?string $navigationLabel = 'Tất cả thông báo';

    protected static ?string $title = 'Tất cả thông báo';

    protected static ?int $navigationSort = 10;

    protected static string|\UnitEnum|null $navigationGroup = 'Thông báo';

    // Tránh khai báo public property với kiểu không được Livewire hỗ trợ (Paginator)

    public function markAsRead(string $notificationId): void {
        $user = Auth::user();
        if ($user instanceof User) {
            $notification = $user->notifications()->find($notificationId);
            if ($notification) {
                $notification->markAsRead();
                $this->dispatch('$refresh');
            }
        }
    }

    public function markAllAsRead(): void {
        $user = Auth::user();
        if ($user instanceof User) {
            $user->unreadNotifications()->update(['read_at' => now()]);
            $this->dispatch('$refresh');
        }
    }

    public function getNotificationsProperty(): LengthAwarePaginator {
        $user = Auth::user();
        if ($user instanceof User) {
            return $user->notifications()
                ->latest()
                ->paginate(20);
        }

        return new \Illuminate\Pagination\LengthAwarePaginator([], 0, 20);
    }

    public function getUnreadCountProperty(): int {
        $user = Auth::user();
        if ($user instanceof User) {
            return $user->unreadNotifications()->count();
        }

        return 0;
    }
}
