<?php

namespace App\Filament\Resources\CollaboratorRegistrations\Actions;

use App\Models\CollaboratorRegistration;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class ApproveRegistrationAction {
    public static function make(): Action {
        return Action::make('approve')
            ->label('Duyệt đăng ký')
            ->icon('heroicon-o-check-circle')
            ->color('success')
            ->requiresConfirmation()
            ->modalHeading('Duyệt đăng ký cộng tác viên')
            ->modalDescription('Bạn có chắc chắn muốn duyệt đăng ký này? Sau khi duyệt, cộng tác viên sẽ được tạo tự động.')
            ->modalSubmitActionLabel('Duyệt')
            ->action(function (CollaboratorRegistration $record) {
                try {
                    $reviewer = Auth::user();
                    $success = $record->approve($reviewer);

                    if ($success) {
                        Notification::make()
                            ->title('Đăng ký đã được duyệt')
                            ->body("Cộng tác viên {$record->full_name} đã được tạo thành công với mã REF: {$record->ref_id}")
                            ->success()
                            ->send();
                    } else {
                        Notification::make()
                            ->title('Lỗi khi duyệt đăng ký')
                            ->body('Có lỗi xảy ra khi duyệt đăng ký. Vui lòng thử lại.')
                            ->danger()
                            ->send();
                    }
                } catch (\Exception $e) {
                    \Log::error('Lỗi khi approve đăng ký cộng tác viên: ' . $e->getMessage());

                    Notification::make()
                        ->title('Lỗi khi duyệt đăng ký')
                        ->body('Có lỗi xảy ra khi duyệt đăng ký. Vui lòng thử lại.')
                        ->danger()
                        ->send();
                }
            });
    }
}
