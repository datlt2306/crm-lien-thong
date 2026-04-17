<?php

namespace App\Filament\Resources\Intakes\Pages;

use App\Filament\Resources\Intakes\IntakeResource;
use App\Models\AnnualQuota;
use App\Models\Quota;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

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

    protected function afterCreate(): void {
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
            $requestedTarget = data_get($intake->settings, 'intake_target_quota');
            $requestedTarget = is_numeric($requestedTarget) ? max(0, (int) $requestedTarget) : null;
            $targetForIntake = $remainingTarget;

            if (!is_null($requestedTarget)) {
                if ($requestedTarget > $remainingTarget) {
                    Notification::make()
                        ->title('Chỉ tiêu đợt vượt mức còn lại')
                        ->body("Bạn nhập {$requestedTarget}, nhưng chỉ còn {$remainingTarget} theo chỉ tiêu năm. Hệ thống tự giới hạn về {$remainingTarget}.")
                        ->warning()
                        ->send();
                } else {
                    $targetForIntake = $requestedTarget;
                }
            }

            $settings = (array) ($intake->settings ?? []);
            data_set($settings, 'intake_target_quota', $targetForIntake);
            $intake->forceFill(['settings' => $settings])->saveQuietly();

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
                    'target_quota' => $targetForIntake,
                    'status' => $mappedStatus,
                    'notes' => $annual->notes,
                ]
            );
        });
    }

}
