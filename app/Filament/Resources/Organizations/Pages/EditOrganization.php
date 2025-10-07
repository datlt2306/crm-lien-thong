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
use Illuminate\Support\Facades\DB;
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
        if (!empty($data['organization_owner_email']) && !empty($data['organization_owner_password'])) {
            $user = User::where('email', $data['organization_owner_email'])->first();
            if ($user) {
                $user->update([
                    'password' => Hash::make($data['organization_owner_password'])
                ]);
            }
        }

        // Loại bỏ password khỏi data trước khi lưu Organization
        unset($data['organization_owner_password'], $data['organization_owner_password_confirmation']);

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
        $majorProgramMappings = [];

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

            // Lưu mapping program_ids cho từng major
            if (!empty($row['program_ids']) && is_array($row['program_ids'])) {
                $majorProgramMappings[$majorId] = $row['program_ids'];
            }
        }

        // Sync majors trước
        $this->record->majors()->sync($syncMajors);

        // Sau đó sync programs cho từng major
        foreach ($majorProgramMappings as $majorId => $programIds) {
            // Tìm major_organization record
            $majorOrgRecord = DB::table('major_organization')
                ->where('organization_id', $this->record->id)
                ->where('major_id', $majorId)
                ->first();

            if ($majorOrgRecord) {
                // Sync programs cho major này
                $syncData = [];
                foreach ($programIds as $programId) {
                    $syncData[] = [
                        'major_organization_id' => $majorOrgRecord->id,
                        'program_id' => $programId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }

                // Xóa programs cũ và thêm mới
                DB::table('major_organization_program')
                    ->where('major_organization_id', $majorOrgRecord->id)
                    ->delete();

                if (!empty($syncData)) {
                    DB::table('major_organization_program')->insert($syncData);
                }
            }
        }

        Log::info('ORG_EDIT::syncTrainingRows:done', [
            'majors_synced' => array_keys($syncMajors),
            'major_program_mappings' => $majorProgramMappings,
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

        // Lưu training_rows trước khi unset
        $trainingRows = $data['training_rows'] ?? [];

        // Không lưu training_rows vào cột của organizations
        unset($data['training_rows']);

        // Cập nhật bản ghi chính
        $record->update($data);
        $this->record = $record;

        // Đồng bộ pivot với dữ liệu đã lưu
        $this->syncTrainingRows(is_array($trainingRows) ? $trainingRows : []);
        return $record;
    }

    protected function getHeaderActions(): array {
        $actions = [
            ViewAction::make()
                ->label('Xem chi tiết'),
        ];

        // Chỉ super admin mới có thể xóa đơn vị
        if (\Illuminate\Support\Facades\Auth::user()?->role === 'super_admin') {
            $actions[] = DeleteAction::make()
                ->label('Xóa đơn vị')
                ->modalHeading('Xóa đơn vị')
                ->modalDescription('Bạn có chắc chắn muốn xóa đơn vị này? Hành động này không thể hoàn tác.')
                ->modalSubmitActionLabel('Xóa')
                ->modalCancelActionLabel('Hủy');
        }

        return $actions;
    }

    protected function getFormActions(): array {
        return [
            $this->getSaveFormAction()
                ->label('Lưu thay đổi')
                ->after(function () {
                    // Refresh form sau khi lưu để hiển thị dữ liệu mới
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),
            $this->getCancelFormAction()
                ->label('Hủy'),
        ];
    }

    public function getTitle(): string {
        return 'Chỉnh sửa đơn vị';
    }

    public function getBreadcrumb(): string {
        return 'Chỉnh sửa đơn vị';
    }

    public function mount(int | string $record): void {
        parent::mount($record);

        $user = \Illuminate\Support\Facades\Auth::user();

        // Kiểm tra quyền truy cập
        if ($user?->role === 'organization_owner' && $this->record->organization_owner_id !== $user->id) {
            abort(403, 'Bạn chỉ có thể chỉnh sửa đơn vị của mình.');
        }

        if (!in_array($user?->role, ['super_admin', 'organization_owner'])) {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }
    }
}
