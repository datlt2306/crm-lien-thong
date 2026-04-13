<?php

namespace App\Filament\Resources\Quotas\Pages;

use App\Models\Intake;
use App\Filament\Resources\Quotas\QuotaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditQuota extends EditRecord {
    protected static string $resource = QuotaResource::class;

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
        $intake = $this->record->intake;
        if ($intake) {
            $data['intake_name'] = $intake->name;
            $data['intake_start_date'] = $intake->start_date;
            $data['intake_end_date'] = $intake->end_date;
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array {
        $intake = $this->record->intake;
        if ($intake) {
            $intake->update([
                'name' => $data['intake_name'] ?? $intake->name,
                'start_date' => $data['intake_start_date'] ?? $intake->start_date,
                'end_date' => $data['intake_end_date'] ?? $intake->end_date,
                'organization_id' => $data['organization_id'] ?? $intake->organization_id,
            ]);
        } else {
            $newIntake = Intake::create([
                'name' => $data['intake_name'] ?? '',
                'start_date' => $data['intake_start_date'] ?? null,
                'end_date' => $data['intake_end_date'] ?? null,
                'organization_id' => $data['organization_id'] ?? null,
                'status' => Intake::STATUS_ACTIVE,
            ]);
            $this->record->update(['intake_id' => $newIntake->id]);
        }
        unset($data['intake_name'], $data['intake_start_date'], $data['intake_end_date']);
        return $data;
    }
}
