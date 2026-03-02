<?php

namespace App\Filament\Resources\Intakes\Pages;

use App\Filament\Resources\Intakes\IntakeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntake extends CreateRecord {
    protected static string $resource = IntakeResource::class;

    public function mount(): void {
        $user = \Illuminate\Support\Facades\Auth::user();

        // Chỉ super_admin và organization_owner mới có thể truy cập
        if (!$user || !in_array($user->role, ['super_admin', 'organization_owner'])) {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array {
        // Tách annual_quota_ids ra khỏi data để xử lý riêng
        $annualQuotaIds = $data['annual_quota_ids'] ?? null;
        unset($data['annual_quota_ids']);
        
        return $data;
    }

    protected function afterCreate(): void {
        // Liên kết chỉ tiêu năm với đợt tuyển sinh sau khi tạo
        $annualQuotaIds = $this->form->getState()['annual_quota_ids'] ?? [];
        
        // Sync các chỉ tiêu năm đã chọn (có thể là mảng rỗng)
        $this->record->annualQuotas()->sync($annualQuotaIds);
    }
}
