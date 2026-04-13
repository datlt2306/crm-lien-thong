<?php

namespace App\Filament\Resources\Intakes\Pages;

use App\Filament\Resources\Intakes\IntakeResource;
use App\Models\AnnualQuota;
use App\Models\Quota;
use Filament\Actions\Action;
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
            Action::make('sync_annual_quotas')
                ->label('Đồng bộ chỉ tiêu năm')
                ->icon('heroicon-o-arrow-path')
                ->color('info')
                ->requiresConfirmation()
                ->modalHeading('Đồng bộ chỉ tiêu năm vào đợt')
                ->modalDescription('Hệ thống sẽ tự động tạo/cập nhật danh sách ngành-hệ (Quota) của đợt này từ dữ liệu Chỉ tiêu năm cùng tổ chức và cùng năm.')
                ->modalSubmitActionLabel('Đồng bộ')
                ->action(function (): void {
                    $intake = $this->record;
                    $year = (int) ($intake->start_date?->format('Y') ?? now()->format('Y'));

                    $annualQuotas = AnnualQuota::query()
                        ->where('organization_id', $intake->organization_id)
                        ->where('year', $year)
                        ->whereIn('status', [AnnualQuota::STATUS_ACTIVE, AnnualQuota::STATUS_FULL, AnnualQuota::STATUS_INACTIVE])
                        ->get();

                    if ($annualQuotas->isEmpty()) {
                        Notification::make()
                            ->title('Không có dữ liệu chỉ tiêu năm')
                            ->body("Chưa tìm thấy AnnualQuota cho tổ chức hiện tại trong năm {$year}.")
                            ->warning()
                            ->send();
                        return;
                    }

                    $created = 0;
                    $updated = 0;

                    DB::transaction(function () use ($annualQuotas, $intake, &$created, &$updated): void {
                        foreach ($annualQuotas as $annual) {
                            $quota = Quota::query()
                                ->where('intake_id', $intake->id)
                                ->where('organization_id', $intake->organization_id)
                                ->where('major_name', $annual->major_name)
                                ->where(function ($query) use ($annual) {
                                    if ($annual->program_name === null) {
                                        $query->whereNull('program_name');
                                    } else {
                                        $query->where('program_name', $annual->program_name);
                                    }
                                })
                                ->first();

                            $mappedStatus = match ($annual->status) {
                                AnnualQuota::STATUS_FULL => Quota::STATUS_FULL,
                                AnnualQuota::STATUS_INACTIVE => Quota::STATUS_INACTIVE,
                                default => Quota::STATUS_ACTIVE,
                            };

                            $payload = [
                                'name' => $annual->name ?: ($annual->major_name ?? 'Chỉ tiêu tuyển sinh'),
                                'major_name' => $annual->major_name,
                                'program_name' => $annual->program_name,
                                'target_quota' => (int) $annual->target_quota,
                                'status' => $mappedStatus,
                                'notes' => $annual->notes,
                            ];

                            if ($quota) {
                                $quota->fill($payload)->save();
                                $updated++;
                                continue;
                            }

                            Quota::create(array_merge($payload, [
                                'organization_id' => $intake->organization_id,
                                'intake_id' => $intake->id,
                                'current_quota' => 0,
                                'pending_quota' => 0,
                                'reserved_quota' => 0,
                            ]));
                            $created++;
                        }
                    });

                    Notification::make()
                        ->title('Đồng bộ thành công')
                        ->body("Đã tạo {$created} và cập nhật {$updated} chỉ tiêu cho đợt này.")
                        ->success()
                        ->send();
                }),
            DeleteAction::make(),
        ];
    }

}
