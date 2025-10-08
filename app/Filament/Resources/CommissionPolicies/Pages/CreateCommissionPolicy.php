<?php

namespace App\Filament\Resources\CommissionPolicies\Pages;

use App\Filament\Resources\CommissionPolicies\CommissionPolicyResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCommissionPolicy extends CreateRecord {
    protected static string $resource = CommissionPolicyResource::class;

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

    protected function mutateFormDataBeforeCreate(array $data): array {
        // Tự động gán organization_id cho organization_owner
        if (Auth::user()->role === 'organization_owner') {
            $organization = \App\Models\Organization::where('organization_owner_id', Auth::user()->id)->first();
            if ($organization) {
                $data['organization_id'] = $organization->id;
            }
        }

        return $data;
    }
}
