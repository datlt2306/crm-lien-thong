<?php

namespace App\Filament\Resources\Intakes\Pages;

use App\Filament\Resources\Intakes\IntakeResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntake extends EditRecord {
    protected static string $resource = IntakeResource::class;

    public function mount(int | string $record): void {
        $user = \Illuminate\Support\Facades\Auth::user();

        // Chỉ super_admin và organization_owner mới có thể truy cập
        if (!$user || !in_array($user->role, ['super_admin', 'organization_owner'])) {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        parent::mount($record);
    }

    protected function getHeaderActions(): array {
        return [
            DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array {
        // Load annual_quota_ids vào form data
        $data['annual_quota_ids'] = $this->record->annualQuotas->pluck('id')->toArray();
        
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array {
        // Tách annual_quota_ids ra khỏi data để xử lý riêng
        $annualQuotaIds = $data['annual_quota_ids'] ?? null;
        unset($data['annual_quota_ids']);
        
        return $data;
    }

    protected function afterSave(): void {
        // Cập nhật liên kết chỉ tiêu năm với đợt tuyển sinh
        $annualQuotaIds = $this->form->getState()['annual_quota_ids'] ?? [];
        
        // Sync các chỉ tiêu năm đã chọn (có thể là mảng rỗng để bỏ link)
        $this->record->annualQuotas()->sync($annualQuotaIds);
    }
}
