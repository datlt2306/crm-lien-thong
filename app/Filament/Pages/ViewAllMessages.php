<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Filament\Actions\Action;
use Illuminate\Support\Facades\Auth;
use Illuminate\Notifications\DatabaseNotification;

class ViewAllMessages extends Page {
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected string $view = 'filament.pages.view-all-messages';
    protected static ?string $title = 'Tất cả tin nhắn';
    protected static ?string $navigationLabel = 'Tin nhắn';
    protected static string|\UnitEnum|null $navigationGroup = 'Hệ thống';
    protected static ?int $navigationSort = 6;

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
                        'message' => 'Đã đánh dấu tất cả tin nhắn là đã đọc'
                    ]);
                }),

            Action::make('delete_all')
                ->label('Xóa tất cả')
                ->icon('heroicon-o-trash')
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Xóa tất cả tin nhắn')
                ->modalDescription('Bạn có chắc chắn muốn xóa tất cả tin nhắn? Hành động này không thể hoàn tác.')
                ->action(function () {
                    Auth::user()->notifications()->delete();

                    $this->dispatch('notify', [
                        'type' => 'success',
                        'message' => 'Đã xóa tất cả tin nhắn'
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
                'message' => 'Đã đánh dấu tin nhắn là đã đọc'
            ]);
        }
    }

    public function deleteMessage(string $notificationId): void {
        $notification = Auth::user()->notifications()->find($notificationId);

        if ($notification) {
            $notification->delete();

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Đã xóa tin nhắn'
            ]);
        }
    }

    public function getMessages() {
        return Auth::user()->notifications()
            ->where('type', 'like', '%Message%')
            ->orWhere('type', 'like', '%Notification%')
            ->latest()
            ->paginate(20);
    }

    public function getUnreadCount(): int {
        return Auth::user()->unreadNotifications()->count();
    }
}
