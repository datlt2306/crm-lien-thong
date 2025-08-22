<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord {
    protected static string $resource = UserResource::class;

    public function getTitle(): string {
        return 'Thêm người dùng mới';
    }
    public function getBreadcrumb(): string {
        return 'Thêm người dùng mới';
    }

    protected function mutateFormDataBeforeCreate(array $data): array {
        // Hash password trước khi tạo user
        if (!empty($data['password'])) {
            $data['password'] = \Illuminate\Support\Facades\Hash::make($data['password']);
        }

        // Tự động verify email cho user mới tạo
        $data['email_verified_at'] = now();

        // Loại bỏ password_confirmation khỏi data
        unset($data['password_confirmation']);

        return $data;
    }

    protected function afterCreate(): void {
        // Gán role cho user mới tạo
        $user = $this->record;
        if (isset($user->role)) {
            try {
                $user->assignRole($user->role);
            } catch (\Exception $e) {
                // Nếu role chưa tồn tại, tạo mới
                if (str_contains($e->getMessage(), 'There is no role named')) {
                    \Spatie\Permission\Models\Role::create([
                        'name' => $user->role,
                        'guard_name' => 'web'
                    ]);
                    $user->assignRole($user->role);
                } else {
                    throw $e;
                }
            }
        }
    }
}
