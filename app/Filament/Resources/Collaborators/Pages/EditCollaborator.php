<?php

namespace App\Filament\Resources\Collaborators\Pages;

use App\Filament\Resources\Collaborators\CollaboratorResource;
use App\Models\User;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Hash;

class EditCollaborator extends EditRecord {
    protected static string $resource = CollaboratorResource::class;

    protected function mutateFormDataBeforeSave(array $data): array {
        // Cập nhật mật khẩu nếu có
        if (!empty($data['email'])) {
            $user = User::where('email', $data['email'])->first();
            if ($user) {
                $password = !empty($data['password']) ? $data['password'] : '123456';
                $user->update([
                    'password' => Hash::make($password)
                ]);
            }
        }

        // Loại bỏ password khỏi data trước khi lưu Collaborator
        unset($data['password'], $data['password_confirmation']);

        return $data;
    }

    protected function getHeaderActions(): array {
        return [
            ViewAction::make(),
            DeleteAction::make(),
        ];
    }

    public function getTitle(): string {
        return 'Chỉnh sửa CTV con';
    }

    public function getBreadcrumb(): string {
        return 'Chỉnh sửa CTV con';
    }
}
