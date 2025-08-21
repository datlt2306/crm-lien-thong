<?php

namespace App\Filament\Resources\CommissionPolicies\Pages;

use App\Filament\Resources\CommissionPolicies\CommissionPolicyResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCommissionPolicy extends EditRecord {
    protected static string $resource = CommissionPolicyResource::class;

    protected function getHeaderActions(): array {
        return [
            DeleteAction::make(),
        ];
    }

    public function getTitle(): string {
        return 'Chỉnh sửa cấu hình hoa hồng';
    }

    public function getBreadcrumb(): string {
        return 'Chỉnh sửa cấu hình hoa hồng';
    }
}
