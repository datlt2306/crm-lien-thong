<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Filament\Resources\Organizations\OrganizationResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class CreateOrganization extends CreateRecord {
    protected static string $resource = OrganizationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array {
        // Đảm bảo có mã đơn vị (code) — sinh từ name nếu trống
        if (empty($data['code']) && !empty($data['name'])) {
            $base = Str::slug($data['name']);
            $code = $base;
            $i = 1;
            while (\App\Models\Organization::where('code', $code)->exists()) {
                $code = $base . '-' . $i++;
            }
            $data['code'] = $code;
        }
        // Nếu có chọn organization_owner_id có sẵn, sử dụng luôn
        if (!empty($data['organization_owner_id'])) {
            // Không cần làm gì thêm, chỉ cần loại bỏ các field không cần thiết
        }
        // Nếu không có organization_owner_id nhưng có email, tạo user mới
        elseif (!empty($data['owner_email'])) {
            $password = !empty($data['owner_password']) ? $data['owner_password'] : '123456';

            $userAccount = User::create([
                'name' => $data['name'] . ' - Chủ đơn vị',
                'email' => $data['owner_email'],
                'password' => Hash::make($password),
                'role' => 'organization_owner',
                'email_verified_at' => now(),
            ]);

            // Gán organization_owner_id cho organization
            $data['organization_owner_id'] = $userAccount->id;
        }
        // Nếu không có cả hai, hiển thị validation error
        else {
            $this->addError('organization_owner_id', '❌ Bắt buộc phải chọn tài khoản có sẵn hoặc tạo tài khoản mới cho chủ đơn vị');
            $this->halt();
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

    public function mount(): void {
        // Chỉ super admin mới được truy cập trang tạo đơn vị
        if (\Illuminate\Support\Facades\Auth::user()?->role !== 'super_admin') {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        parent::mount();
    }

    protected function getFormActions(): array {
        return [
            $this->getCreateFormAction()
                ->label('Tạo đơn vị'),
            $this->getCancelFormAction()
                ->label('Hủy'),
        ];
    }
}
