<?php

namespace App\Filament\Resources\CommissionPolicies\Pages;

use App\Filament\Resources\CommissionPolicies\CommissionPolicyResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCommissionPolicy extends CreateRecord {
    protected static string $resource = CommissionPolicyResource::class;

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    public function getTitle(): string {
        return 'Tạo cấu hình hoa hồng mới';
    }

    public function getBreadcrumb(): string {
        return 'Tạo cấu hình hoa hồng mới';
    }

    protected function getFormActions(): array {
        return [
            $this->getCreateFormAction()
                ->label('Tạo chính sách'),
            $this->getCancelFormAction()
                ->label('Hủy'),
        ];
    }

}
