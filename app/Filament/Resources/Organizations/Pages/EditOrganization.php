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

    protected function mutateFormDataBeforeSave(array $data): array {
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

        // Cập nhật bản ghi chính
        $record->update($data);
        $this->record = $record;

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
