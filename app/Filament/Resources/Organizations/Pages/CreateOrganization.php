<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Filament\Resources\Organizations\OrganizationResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateOrganization extends CreateRecord {
    protected static string $resource = OrganizationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array {
        // Tạo User account cho chủ đơn vị
        if (!empty($data['owner_email'])) {
            $password = !empty($data['owner_password']) ? $data['owner_password'] : '123456';

            $userAccount = User::create([
                'name' => $data['contact_name'] ?: $data['name'],
                'email' => $data['owner_email'],
                'password' => Hash::make($password),
                'role' => 'user',
            ]);

            // Gán role 'user' cho chủ đơn vị
            $userAccount->assignRole('user');

            // Gán owner_id cho organization
            $data['owner_id'] = $userAccount->id;
        }

        // Loại bỏ password khỏi data trước khi tạo Organization
        unset($data['owner_password'], $data['owner_password_confirmation']);

        return $data;
    }

    public function getTitle(): string {
        return 'Tạo đơn vị';
    }

    public function getBreadcrumb(): string {
        return 'Tạo đơn vị';
    }
}
