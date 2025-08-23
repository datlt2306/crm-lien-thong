<?php

namespace App\Filament\Resources\CommissionPolicies\Pages;

use App\Filament\Resources\CommissionPolicies\CommissionPolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCommissionPolicy extends EditRecord {
    protected static string $resource = CommissionPolicyResource::class;

    protected function getHeaderActions(): array {
        return [
            DeleteAction::make()
                ->label('Xóa chính sách')
                ->modalHeading('Xóa chính sách hoa hồng')
                ->modalDescription('Bạn có chắc chắn muốn xóa chính sách hoa hồng này? Hành động này không thể hoàn tác.')
                ->modalSubmitActionLabel('Xóa')
                ->modalCancelActionLabel('Hủy'),
        ];
    }

    protected function getFormActions(): array {
        return [
            $this->getSaveFormAction()
                ->label('Lưu thay đổi'),
            $this->getCancelFormAction()
                ->label('Hủy'),
        ];
    }

    public function getTitle(): string {
        return 'Chỉnh sửa cấu hình hoa hồng';
    }

    public function getBreadcrumb(): string {
        return 'Chỉnh sửa cấu hình hoa hồng';
    }
}
