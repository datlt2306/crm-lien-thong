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
        // Loại bỏ các field không cần thiết
        unset($data['organization_owner_id']); // Chủ đơn vị không được thay đổi organization_owner

        return $data;
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

        // Cập nhật bản ghi chính
        $record->update($data);
        $this->record = $record;

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
