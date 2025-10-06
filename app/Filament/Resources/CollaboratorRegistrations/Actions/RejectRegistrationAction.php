<?php

namespace App\Filament\Resources\CollaboratorRegistrations\Actions;

use App\Models\CollaboratorRegistration;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class RejectRegistrationAction {
    public static function make(): Action {
        return Action::make('reject')
            ->label('Từ chối đăng ký')
            ->icon('heroicon-o-x-circle')
            ->color('danger')
            ->form([
                Textarea::make('rejection_reason')
                    ->label('Lý do từ chối')
                    ->required()
                    ->rows(3)
                    ->placeholder('Nhập lý do từ chối đăng ký...')
                    ->helperText('Lý do này sẽ được gửi cho người đăng ký'),
            ])
            ->requiresConfirmation()
            ->modalHeading('Từ chối đăng ký cộng tác viên')
            ->modalDescription('Bạn có chắc chắn muốn từ chối đăng ký này?')
            ->modalSubmitActionLabel('Từ chối')
            ->action(function (CollaboratorRegistration $record, array $data) {
                try {
                    $reviewer = Auth::user();
                    $success = $record->reject($reviewer, $data['rejection_reason']);

                    if ($success) {
                        Notification::make()
                            ->title('Đăng ký đã bị từ chối')
                            ->body("Đăng ký của {$record->full_name} đã bị từ chối với lý do: {$data['rejection_reason']}")
                            ->warning()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Lỗi khi từ chối đăng ký')
                            ->body('Có lỗi xảy ra khi từ chối đăng ký. Vui lòng thử lại.')
                            ->danger()
                            ->send();
                    }
                } catch (\Exception $e) {
                    \Log::error('Lỗi khi reject đăng ký cộng tác viên: ' . $e->getMessage());

                    Notification::make()
                        ->title('Lỗi khi từ chối đăng ký')
                        ->body('Có lỗi xảy ra khi từ chối đăng ký. Vui lòng thử lại.')
                        ->danger()
                        ->send();
                }
            });
    }
}
