<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Gate;

class EditUser extends EditRecord {
    protected static string $resource = UserResource::class;

    protected $selectedOrganizationId = null;

    public function mount(int | string $record): void {
        parent::mount($record);

        // Kiểm tra quyền update
        if (!Gate::allows('update', $this->record)) {
            abort(403, 'Bạn không có quyền chỉnh sửa người dùng này.');
        }
    }

    protected function getHeaderActions(): array {
        return [
            ViewAction::make()
                ->label('Xem chi tiết'),
            DeleteAction::make()
                ->label('Xóa người dùng')
                ->modalHeading('Xóa người dùng')
                ->modalDescription('Bạn có chắc chắn muốn xóa người dùng này? Hành động này không thể hoàn tác.')
                ->modalSubmitActionLabel('Xóa')
                ->modalCancelActionLabel('Hủy'),
        ];
    }

    public function getTitle(): string {
        return 'Chỉnh sửa người dùng';
    }

    public function getBreadcrumb(): string {
        return 'Chỉnh sửa người dùng';
    }

    protected function getFormActions(): array {
        return [
            $this->getSaveFormAction()
                ->label('Lưu thay đổi'),
            $this->getCancelFormAction()
                ->label('Hủy'),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array {
        // Nếu password trống, không cập nhật password
        if (empty($data['password'])) {
            unset($data['password']);
        } else {
            // Hash password nếu có nhập
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        }

        // Lưu organization_id trực tiếp vào users
        $currentUser = \Illuminate\Support\Facades\Auth::user();
        if ($currentUser && $currentUser->role === 'organization_owner') {
            // Owner: cưỡng chế user thuộc đơn vị của owner
            $org = \App\Models\Organization::where('organization_owner_id', $currentUser->id)->first();
            if ($org) {
                $this->selectedOrganizationId = $org->id;
                $data['organization_id'] = $org->id;
            }
        } else {
            if (isset($data['organization_id'])) {
                $this->selectedOrganizationId = $data['organization_id'];
            }
        }

        // Loại bỏ password_confirmation khỏi data
        unset($data['password_confirmation']);


        // Xử lý role - cập nhật role trong database
        if (isset($data['role'])) {
            $user = $this->record;
            $user->role = $data['role'];
            $user->save();

            // Cập nhật role trong Spatie Permission
            try {
                $user->syncRoles([$data['role']]);
            } catch (\Exception $e) {
                // Nếu role chưa tồn tại, tạo mới
                if (str_contains($e->getMessage(), 'There is no role named')) {
                    \Spatie\Permission\Models\Role::create([
                        'name' => $data['role'],
                        'guard_name' => 'web'
                    ]);
                    $user->syncRoles([$data['role']]);
                } else {
                    throw $e;
                }
            }
        }

        return $data;
    }

    protected function afterSave(): void {
        // Cập nhật Collaborator record nếu có thay đổi organization_id
        if ($this->selectedOrganizationId !== null) {
            $user = $this->record;
            // Đồng bộ users.organization_id
            $user->organization_id = $this->selectedOrganizationId;
            $user->save();

            // Tìm Collaborator record hiện tại
            $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();

            if ($collaborator) {
                // Cập nhật organization_id của Collaborator
                $updatePayload = [
                    'organization_id' => $this->selectedOrganizationId,
                    'full_name' => $user->name,
                ];
                // Chỉ cập nhật phone khi có giá trị; nếu trùng -> báo lỗi validation
                if (!empty($user->phone)) {
                    if (\App\Models\Collaborator::where('phone', $user->phone)->where('id', '!=', $collaborator->id)->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'phone' => ['Số điện thoại đã được sử dụng.'],
                        ]);
                    }
                    $updatePayload['phone'] = $user->phone;
                }
                $collaborator->update($updatePayload);
            } else {
                // Nếu chưa có Collaborator record, chỉ tạo mới khi user là CTV và có phone hợp lệ
                if ($user->role === 'ctv') {
                    if (empty($user->phone)) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'phone' => ['Số điện thoại là bắt buộc cho CTV.'],
                        ]);
                    }
                    if (\App\Models\Collaborator::where('phone', $user->phone)->exists()) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'phone' => ['Số điện thoại đã được sử dụng.'],
                        ]);
                    }
                    \App\Models\Collaborator::create([
                        'full_name' => $user->name,
                        'email' => $user->email,
                        'phone' => $user->phone,
                        'organization_id' => $this->selectedOrganizationId,
                        'status' => 'active',
                    ]);
                }
            }

            // Reset selectedOrganizationId sau khi xử lý
            $this->selectedOrganizationId = null;
        }
    }
}
