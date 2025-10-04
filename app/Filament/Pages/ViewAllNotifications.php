<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class ViewAllNotifications extends Page {
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-bell';
    protected string $view = 'filament.pages.view-all-notifications';
    protected static ?string $title = 'Tất cả thông báo';
    protected static ?string $navigationLabel = 'Thông báo';
    protected static ?int $navigationSort = 100;

    public function getHeaderActions(): array {
        return [
            Action::make('mark_all_read')
                ->label('Đánh dấu tất cả đã đọc')
                ->icon('heroicon-o-check')
                ->color('success')
                ->visible(fn() => Auth::user()->unreadNotifications()->count() > 0)
                ->action(function () {
                    Auth::user()->unreadNotifications()->update(['read_at' => now()]);

                    $this->dispatch('notify', [
                        'type' => 'success',
                        'message' => 'Đã đánh dấu tất cả thông báo là đã đọc'
                    ]);
                }),
        ];
    }

    public function markAsRead(string $notificationId): void {
        $notification = Auth::user()->notifications()->find($notificationId);

        if ($notification && !$notification->read_at) {
            $notification->markAsRead();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Đã đánh dấu thông báo là đã đọc'
            ]);
        }
    }

    public function getNotifications() {
        return Auth::user()->notifications()->latest()->paginate(20);
    }
}
