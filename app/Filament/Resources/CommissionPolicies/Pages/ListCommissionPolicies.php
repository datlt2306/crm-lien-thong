<?php

namespace App\Filament\Resources\CommissionPolicies\Pages;

use App\Filament\Resources\CommissionPolicies\CommissionPolicyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommissionPolicies extends ListRecords {
    protected static string $resource = CommissionPolicyResource::class;

    protected function getHeaderActions(): array {
        return [
            CreateAction::make()
                ->label('Thêm chính sách mới'),
        ];
    }

    public function getTitle(): string {
        return 'Cấu hình hoa hồng';
    }

    public function getBreadcrumb(): string {
        return 'Cấu hình hoa hồng';
    }
}
