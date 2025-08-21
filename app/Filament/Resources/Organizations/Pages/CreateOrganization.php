<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Filament\Resources\Organizations\OrganizationResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;

class CreateOrganization extends CreateRecord {
    protected static string $resource = OrganizationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array {
        // Nếu có chọn owner_id có sẵn, sử dụng luôn
        if (!empty($data['owner_id'])) {
            // Không cần làm gì thêm, chỉ cần loại bỏ các field không cần thiết
        }
        // Nếu không có owner_id nhưng có email, tạo user mới
        elseif (!empty($data['owner_email'])) {
            $password = !empty($data['owner_password']) ? $data['owner_password'] : '123456';

            $userAccount = User::create([
                'name' => $data['name'] . ' - Chủ đơn vị',
                'email' => $data['owner_email'],
                'password' => Hash::make($password),
                'role' => 'chủ đơn vị',
                'email_verified_at' => now(),
            ]);

            // Gán owner_id cho organization
            $data['owner_id'] = $userAccount->id;
        }
        // Nếu không có cả hai, báo lỗi
        else {
            throw new \Exception('Phải chọn tài khoản có sẵn hoặc tạo tài khoản mới cho chủ đơn vị');
        }

        // Loại bỏ các field không cần thiết
        unset($data['owner_email'], $data['owner_password'], $data['owner_password_confirmation']);

        return $data;
    }

    public function getTitle(): string {
        return 'Tạo đơn vị';
    }

    public function getBreadcrumb(): string {
        return 'Tạo đơn vị';
    }
}
