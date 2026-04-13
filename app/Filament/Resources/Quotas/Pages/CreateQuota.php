<?php

namespace App\Filament\Resources\Quotas\Pages;

use App\Models\Intake;
use App\Filament\Resources\Quotas\QuotaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuota extends CreateRecord {
    protected static string $resource = QuotaResource::class;

    public function mount(): void {
        $user = \Illuminate\Support\Facades\Auth::user();

        // Chỉ super_admin và organization_owner mới có thể truy cập
        if (!$user || !in_array($user->role, ['super_admin', 'organization_owner'])) {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        parent::mount();
    }

    protected function mutateFormDataBeforeCreate(array $data): array {
        $name = $data['intake_name'] ?? '';
        $start = $data['intake_start_date'] ?? null;
        $end = $data['intake_end_date'] ?? null;
        $orgId = $data['organization_id'] ?? null;

        $intake = Intake::query()
            ->where('name', $name)
            ->where('start_date', $start)
            ->where('end_date', $end)
            ->where('organization_id', $orgId)
            ->first();

        if (!$intake) {
            $intake = Intake::create([
                'name' => $name,
                'start_date' => $start,
                'end_date' => $end,
                'organization_id' => $orgId,
                'status' => Intake::STATUS_ACTIVE,
            ]);
        }
        $data['intake_id'] = $intake->id;
        unset($data['intake_name'], $data['intake_start_date'], $data['intake_end_date']);
        return $data;
    }
}
