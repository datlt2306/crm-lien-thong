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

    protected function mutateFormDataBeforeFill(array $data): array {
        $queryMajor = trim((string) request()->query('major_name', ''));
        $queryProgram = trim((string) request()->query('program_name', ''));
        $queryYear = trim((string) request()->query('year', ''));

        if ($queryMajor !== '') {
            data_set($data, 'settings.annual_quota_major_name', $queryMajor);
        }

        if ($queryProgram !== '') {
            data_set($data, 'settings.annual_quota_program_name', $queryProgram);
        }

        if ($queryYear !== '' && preg_match('/^\d{4}$/', $queryYear) === 1) {
            data_set($data, 'settings.annual_quota_year', $queryYear);
        }

        $major = data_get($data, 'settings.annual_quota_major_name');
        $program = data_get($data, 'settings.annual_quota_program_name');
        $existingTarget = data_get($data, 'settings.intake_target_quota');

        if ((is_null($existingTarget) || $existingTarget === '') && !empty($major) && !empty($program) && !empty($this->record?->id)) {
            $quotaTarget = Quota::query()
                ->where('intake_id', $this->record->id)
                ->where('major_name', $major)
                ->where('program_name', $program)
                ->value('target_quota');

            if (!is_null($quotaTarget)) {
                data_set($data, 'settings.intake_target_quota', (int) $quotaTarget);
            }
        }

        return $data;
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
