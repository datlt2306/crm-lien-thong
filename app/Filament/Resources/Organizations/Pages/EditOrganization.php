<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Filament\Resources\Organizations\OrganizationResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditOrganization extends EditRecord {
    protected static string $resource = OrganizationResource::class;

    protected function mutateFormDataBeforeSave(array $data): array {
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
