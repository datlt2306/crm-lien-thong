<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Filament\Resources\Organizations\OrganizationResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CreateOrganization extends CreateRecord {
    protected static string $resource = OrganizationResource::class;

    /** @var array<int,array<string,mixed>> */
    protected array $pendingTrainingRows = [];

    protected function mutateFormDataBeforeCreate(array $data): array {
        // Đảm bảo có mã đơn vị (code) — sinh từ name nếu trống
        if (empty($data['code']) && !empty($data['name'])) {
            $base = Str::slug($data['name']);
            $code = $base;
            $i = 1;
            while (\App\Models\Organization::where('code', $code)->exists()) {
                $code = $base . '-' . $i++;
            }
            $data['code'] = $code;
        }
        // Tách training_rows ra để sync pivot sau khi tạo record
        $this->pendingTrainingRows = Arr::pull($data, 'training_rows', []);
        // Nếu có chọn owner_id có sẵn, sử dụng luôn
        if (!empty($data['owner_id'])) {
            // Không cần làm gì thêm, chỉ cần loại bỏ các field không cần thiết
        }
        // Nếu không có owner_id nhưng có email, tạo user mới
        elseif (!empty($data['owner_email'])) {
            $password = !empty($data['owner_password']) ? $data['owner_password'] : '123456';

            $userAccount = User::create([
                'name' => $data['name'] . ' - Chủ đơn vị',
                'email' => $data['owner_email'],
                'password' => Hash::make($password),
                'role' => 'chủ đơn vị',
                'email_verified_at' => now(),
            ]);

            // Gán owner_id cho organization
            $data['owner_id'] = $userAccount->id;
        }
        // Nếu không có cả hai, hiển thị validation error
        else {
            $this->addError('owner_id', '❌ Bắt buộc phải chọn tài khoản có sẵn hoặc tạo tài khoản mới cho chủ đơn vị');
            $this->halt();
        }

        // Loại bỏ các field không cần thiết
        unset($data['owner_email'], $data['owner_password'], $data['owner_password_confirmation']);

        return $data;
    }

    protected function afterCreate(): void {
        $rows = $this->pendingTrainingRows;
        if (empty($rows)) {
            try {
                $state = method_exists($this, 'getForm') ? $this->getForm()->getState() : [];
                $rows = \Illuminate\Support\Arr::get($state, 'training_rows', []);
            } catch (\Throwable) {
                $rows = [];
            }
        }
        Log::info('ORG_CREATE::afterCreate:rows', ['org_id' => $this->record?->id, 'rows' => $rows]);
        $this->syncTrainingRows($rows);
    }

    /**
     * Allow per-row save during create by creating a draft organization if needed,
     * then syncing the current training_rows state.
     */
    public function syncTrainingRowsFromAction(): void {
        // Read current form state
        $state = [];
        try {
            $state = method_exists($this, 'getForm') ? $this->getForm()->getState() : [];
        } catch (\Throwable) {
            $state = [];
        }
        Log::info('ORG_CREATE::syncTrainingRowsFromAction:state', ['state' => $state]);

        // Ensure there is a record (draft) to attach pivots
        if (!$this->record) {
            $name = $state['name'] ?? 'Draft Organization';
            $code = $state['code'] ?? null;
            if (empty($code) && !empty($name)) {
                $base = Str::slug($name);
                $code = $base;
                $i = 1;
                while (\App\Models\Organization::where('code', $code)->exists()) {
                    $code = $base . '-' . $i++;
                }
            }

            $ownerId = $state['owner_id'] ?? null;
            $this->record = \App\Models\Organization::create([
                'name' => $name,
                'code' => $code ?: ('draft-' . uniqid()),
                'owner_id' => $ownerId,
                'status' => 'inactive',
            ]);
            Log::info('ORG_CREATE::draftCreated', ['org_id' => $this->record->id]);
        } else {
            // Keep name/code in sync when user edits then presses per-row save
            $dataUpdate = [];
            if (!empty($state['name'])) {
                $dataUpdate['name'] = $state['name'];
            }
            if (!empty($state['code'])) {
                $dataUpdate['code'] = $state['code'];
            }
            if (!empty($state['owner_id'])) {
                $dataUpdate['owner_id'] = $state['owner_id'];
            }
            if (!empty($dataUpdate)) {
                $this->record->update($dataUpdate);
            }
        }

        $rows = $state['training_rows'] ?? [];
        Log::info('ORG_CREATE::syncTrainingRowsFromAction:rows', ['org_id' => $this->record?->id, 'rows' => $rows]);
        $this->syncTrainingRows(is_array($rows) ? $rows : []);

        \Filament\Notifications\Notification::make()
            ->title('Đã lưu dòng cấu hình vào bản nháp đơn vị')
            ->success()
            ->send();
    }

    /**
     * When user finally submits create, if a draft exists, update it instead of creating new.
     */
    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model {
        $rows = Arr::pull($data, 'training_rows', []);
        Log::info('ORG_CREATE::handleRecordCreation:data', $data);
        if ($this->record) {
            // Update draft record
            $this->record->update($data);
            Log::info('ORG_CREATE::handleRecordCreation:rows_draft', ['org_id' => $this->record->id, 'rows' => $rows]);
            $this->syncTrainingRows($rows ?? []);
            return $this->record;
        }
        // No draft: create fresh
        $model = static::getModel()::create($data);
        $this->record = $model;
        Log::info('ORG_CREATE::handleRecordCreation:rows_new', ['org_id' => $this->record->id, 'rows' => $rows]);
        $this->syncTrainingRows($rows ?? []);
        return $model;
    }

    private function syncTrainingRows(array $rows): void {
        if (!is_array($rows)) return;
        Log::info('ORG_CREATE::syncTrainingRows:rows', [
            'org_id' => $this->record?->id,
            'rows' => $rows,
        ]);
        $syncMajors = [];
        $selectedProgramIds = null;
        foreach ($rows as $row) {
            $majorId = $row['major_id'] ?? null;
            if (!$majorId) continue;
            $quota = (int) (($row['quota'] ?? 0));
            $intakes = null;
            if (isset($row['intake_months']) && is_array($row['intake_months'])) {
                // Sắp xếp tháng theo thứ tự tăng dần
                $months = array_values((array) $row['intake_months']);
                sort($months, SORT_NUMERIC);
                $intakes = json_encode($months);
            }
            $syncMajors[$majorId] = [
                'quota' => $quota,
                'intake_months' => $intakes,
            ];
            if (!empty($row['program_ids']) && is_array($row['program_ids'])) {
                $selectedProgramIds = $row['program_ids'];
            }
        }
        $this->record->majors()->sync($syncMajors);
        $this->record->programs()->sync(array_values(array_unique($selectedProgramIds ?? [])));
        Log::info('ORG_CREATE::syncTrainingRows:done', [
            'majors_synced' => array_keys($syncMajors),
            'programs_synced' => array_values(array_unique($selectedProgramIds ?? [])),
        ]);
    }

    public function getTitle(): string {
        return 'Tạo đơn vị';
    }

    public function getBreadcrumb(): string {
        return 'Tạo đơn vị';
    }

    protected function getFormActions(): array {
        return [
            $this->getCreateFormAction()
                ->label('Tạo đơn vị'),
            $this->getCancelFormAction()
                ->label('Hủy'),
        ];
    }
}
