<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Filament\Resources\Organizations\OrganizationResource;
use App\Models\Organization;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\Model;

class EditMyOrganization extends EditRecord {
    protected static string $resource = OrganizationResource::class;

    /** @var array<int,array<string,mixed>> */
    protected array $pendingTrainingRows = [];

    public function mount(int | string $record): void {
        $user = \Illuminate\Support\Facades\Auth::user();

        // Chỉ organization_owner được truy cập
        if ($user?->role !== 'organization_owner') {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        // Tìm đơn vị của organization_owner
        $organization = Organization::where('organization_owner_id', $user->id)->first();

        if (!$organization) {
            abort(404, 'Không tìm thấy đơn vị của bạn.');
        }

        // Mount với record của đơn vị
        parent::mount($organization->id);
    }

    protected function mutateFormDataBeforeSave(array $data): array {
        // Lưu tạm training_rows nhưng KHÔNG loại bỏ khỏi $data
        $this->pendingTrainingRows = $data['training_rows'] ?? [];

        // Loại bỏ các field không cần thiết
        unset($data['organization_owner_id']); // Chủ đơn vị không được thay đổi organization_owner

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
        Log::info('ORG_MY_EDIT::syncTrainingRowsFromAction:state', [
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
        Log::info('ORG_MY_EDIT::syncTrainingRows:rows', [
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

        Log::info('ORG_MY_EDIT::syncTrainingRows:done', [
            'majors_synced' => array_keys($syncMajors),
            'major_program_mappings' => $majorProgramMappings,
        ]);
    }

    protected function handleRecordUpdate(Model $record, array $data): Model {
        Log::info('ORG_MY_EDIT::handleRecordUpdate:data', $data);

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
        return [
            \Filament\Actions\ViewAction::make()
                ->label('Xem chi tiết'),
        ];
    }

    protected function getFormActions(): array {
        return [
            $this->getSaveFormAction()
                ->label('Lưu thay đổi')
                ->after(function () {
                    // Refresh form sau khi lưu để hiển thị dữ liệu mới
                    $this->redirect($this->getResource()::getUrl('my-organization'));
                }),
            $this->getCancelFormAction()
                ->label('Hủy'),
        ];
    }

    public function getTitle(): string {
        return 'Cấu hình đơn vị';
    }

    public function getBreadcrumb(): string {
        return 'Cấu hình đơn vị';
    }
}
