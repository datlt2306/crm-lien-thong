<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Filament\Resources\Organizations\OrganizationResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use App\Models\Organization;

class EditOrganization extends EditRecord {
    protected static string $resource = OrganizationResource::class;

    /** @var array<int,array<string,mixed>> */
    protected array $pendingTrainingRows = [];

    protected function mutateFormDataBeforeSave(array $data): array {
        // Lưu tạm training_rows nhưng KHÔNG loại bỏ khỏi $data
        $this->pendingTrainingRows = $data['training_rows'] ?? [];
        // Cập nhật mật khẩu nếu có
        if (!empty($data['owner_email']) && !empty($data['owner_password'])) {
            $user = User::where('email', $data['owner_email'])->first();
            if ($user) {
                $user->update([
                    'password' => Hash::make($data['owner_password'])
                ]);
            }
        }

        // Loại bỏ password khỏi data trước khi lưu Organization
        unset($data['owner_password'], $data['owner_password_confirmation']);

        return $data;
    }

    protected function afterSave(): void {
        // no-op: handled in handleRecordUpdate
    }

    public function syncTrainingRowsFromAction(): void {
        // Log state trước khi lưu để debug
        try {
            $state = method_exists($this, 'getForm') ? $this->getForm()->getState() : [];
        } catch (\Throwable) {
            $state = [];
        }
        Log::info('ORG_EDIT::syncTrainingRowsFromAction:state', [
            'state' => $state,
        ]);

        // Gọi lưu chuẩn để chạy afterSave (trong đó syncTrainingRows)
        $this->save();
        \Filament\Notifications\Notification::make()
            ->title('Đã lưu cấu hình đào tạo')
            ->success()
            ->send();
    }

    private function syncTrainingRows(array $rows): void {
        if (!is_array($rows)) return;
        Log::info('ORG_EDIT::syncTrainingRows:rows', [
            'org_id' => $this->record?->id,
            'rows' => $rows,
        ]);
        $syncMajors = [];
        $selectedProgramIds = null;
        foreach ($rows as $row) {
            $majorId = $row['major_id'] ?? null;
            if (!$majorId) continue;
            $quota = (int) (($row['quota'] ?? 0));
            $intakes = isset($row['intake_months']) ? json_encode(array_values((array) $row['intake_months'])) : null;
            $syncMajors[$majorId] = [
                'quota' => $quota,
                'intake_months' => $intakes,
            ];
            if (!empty($row['program_ids']) && is_array($row['program_ids'])) {
                // Dùng lựa chọn của dòng cuối cùng làm chuẩn cho toàn đơn vị
                $selectedProgramIds = $row['program_ids'];
            }
        }
        $this->record->majors()->sync($syncMajors);
        $this->record->programs()->sync(array_values(array_unique($selectedProgramIds ?? [])));
        Log::info('ORG_EDIT::syncTrainingRows:done', [
            'majors_synced' => array_keys($syncMajors),
            'programs_synced' => array_values(array_unique($selectedProgramIds ?? [])),
        ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model {
        Log::info('ORG_EDIT::handleRecordUpdate:data', $data);
        // Validate unique name friendly (tránh vấp UNIQUE ở DB)
        if (!empty($data['name'])) {
            $exists = Organization::where('name', $data['name'])
                ->where('id', '!=', $record->id)
                ->exists();
            if ($exists) {
                throw ValidationException::withMessages([
                    'name' => 'Tên đơn vị đã tồn tại, vui lòng chọn tên khác.',
                ]);
            }
        }
        // Không lưu training_rows vào cột của organizations
        unset($data['training_rows']);

        // Cập nhật bản ghi chính
        $record->update($data);
        $this->record = $record;

        // Lấy state hiện tại từ form (đảm bảo có dữ liệu của repeater)
        try {
            $state = method_exists($this, 'getForm') ? $this->getForm()->getState() : [];
        } catch (\Throwable) {
            $state = [];
        }
        Log::info('ORG_EDIT::handleRecordUpdate:state', [
            'training_rows' => $state['training_rows'] ?? null,
        ]);
        $rows = $state['training_rows'] ?? $this->pendingTrainingRows ?? [];

        // Đồng bộ pivot
        $this->syncTrainingRows(is_array($rows) ? $rows : []);
        return $record;
    }

    protected function getHeaderActions(): array {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    public function getTitle(): string {
        return 'Chỉnh sửa đơn vị';
    }

    public function getBreadcrumb(): string {
        return 'Chỉnh sửa đơn vị';
    }
}
