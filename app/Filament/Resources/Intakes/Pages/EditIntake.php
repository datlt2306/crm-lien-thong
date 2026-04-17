<?php

namespace App\Filament\Resources\Intakes\Pages;

use App\Filament\Resources\Intakes\IntakeResource;
use App\Models\AnnualQuota;
use App\Models\Intake;
use App\Models\Quota;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\DB;

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

    protected function afterSave(): void {
        $intake = $this->record;
        $year = (int) (data_get($intake->settings, 'annual_quota_year')
            ?? $intake->start_date?->format('Y')
            ?? now()->format('Y'));
        $major = data_get($intake->settings, 'annual_quota_major_name');
        $program = data_get($intake->settings, 'annual_quota_program_name');

        if (empty($major) || empty($program)) {
            return;
        }

        $annual = AnnualQuota::query()
            ->where('organization_id', $intake->organization_id)
            ->where('year', $year)
            ->where('major_name', $major)
            ->where('program_name', $program)
            ->first();

        if (!$annual) {
            Notification::make()
                ->title('Chưa thể ánh xạ chỉ tiêu')
                ->body('Không tìm thấy chỉ tiêu năm tương ứng với Năm/Ngành/Hệ đã chọn.')
                ->warning()
                ->send();
            return;
        }

        DB::transaction(function () use ($intake, $annual, $year): void {
            $allocatedToOtherIntakes = Quota::query()
                ->where('organization_id', $intake->organization_id)
                ->where('intake_id', '!=', $intake->id)
                ->where('major_name', $annual->major_name)
                ->where('program_name', $annual->program_name)
                ->whereHas('intake', fn($query) => $query->whereYear('start_date', $year))
                ->sum('target_quota');

            $remainingTarget = max(0, (int) $annual->target_quota - (int) $allocatedToOtherIntakes);
            $mappedStatus = match ($annual->status) {
                AnnualQuota::STATUS_FULL => Quota::STATUS_FULL,
                AnnualQuota::STATUS_INACTIVE => Quota::STATUS_INACTIVE,
                default => Quota::STATUS_ACTIVE,
            };

            Quota::query()->updateOrCreate(
                [
                    'intake_id' => $intake->id,
                    'organization_id' => $intake->organization_id,
                    'major_name' => $annual->major_name,
                    'program_name' => $annual->program_name,
                ],
                [
                    'name' => $annual->name ?: ($annual->major_name ?? 'Chỉ tiêu tuyển sinh'),
                    'target_quota' => $remainingTarget,
                    'status' => $mappedStatus,
                    'notes' => $annual->notes,
                ]
            );
        });
    }

}
