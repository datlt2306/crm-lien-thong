<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Student;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditStudent extends EditRecord {
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array {
        return [
            ViewAction::make()
                ->label('Xem chi tiết'),
            DeleteAction::make()
                ->label('Xóa học viên')
                ->modalHeading('Xóa học viên')
                ->modalDescription('Bạn có chắc chắn muốn xóa học viên này? Hành động này không thể hoàn tác.')
                ->modalSubmitActionLabel('Xóa')
                ->modalCancelActionLabel('Hủy'),
        ];
    }

    public function getTitle(): string {
        return 'Chỉnh sửa học viên';
    }

    public function getBreadcrumb(): string {
        return 'Chỉnh sửa học viên';
    }

    protected function getValidationRules(): array {
        return Student::getValidationRules();
    }

    public static function canAccess(array $parameters = []): bool {
        $user = Auth::user();
        return $user && in_array($user->role, ['super_admin', 'chủ đơn vị']);
    }
}
