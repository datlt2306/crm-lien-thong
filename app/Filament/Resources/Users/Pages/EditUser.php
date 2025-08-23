<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord {
    protected static string $resource = UserResource::class;

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
}
