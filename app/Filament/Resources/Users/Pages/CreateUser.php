<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Gate;

class CreateUser extends CreateRecord {
    protected static string $resource = UserResource::class;

    protected $selectedOrganizationId = null;

    public function mount(): void {
        parent::mount();

        // Kiểm tra quyền create
        if (!Gate::allows('create', User::class)) {
            abort(403, 'Bạn không có quyền tạo người dùng mới.');
        }
    }

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

        // Nếu chưa có role được chọn, mặc định là 'ctv'
        if (empty($data['role'])) {
            $data['role'] = 'ctv';
        }

        // Lưu organization_id trực tiếp vào users
        $currentUser = \Illuminate\Support\Facades\Auth::user();
        if ($currentUser && $currentUser->role === 'organization_owner') {
            // Owner: luôn gán user mới vào đơn vị của owner
            $org = \App\Models\Organization::where('organization_owner_id', $currentUser->id)->first();
            if ($org) {
                $data['organization_id'] = $org->id;
                $this->selectedOrganizationId = $org->id;
            }
        } else {
            if (isset($data['organization_id'])) {
                $this->selectedOrganizationId = $data['organization_id'];
            }
        }

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

        // Tạo Collaborator record cho user có vai trò 'ctv'
        $currentUser = \Illuminate\Support\Facades\Auth::user();
        $shouldCreateCollaborator = false;
        $orgId = null;

        if ($user->role === 'ctv') {
            if ($currentUser && $currentUser->role === 'organization_owner') {
                // Owner tạo CTV: gán về tổ chức của owner
                $shouldCreateCollaborator = true;
                $org = \App\Models\Organization::where('organization_owner_id', $currentUser->id)->first();
                if ($org) {
                    $orgId = $org->id;
                }
            } elseif ($currentUser && $currentUser->role === 'super_admin') {
                // Super admin tạo CTV: gán theo lựa chọn
                $shouldCreateCollaborator = true;
                if ($this->selectedOrganizationId) {
                    $orgId = $this->selectedOrganizationId;
                }
            }
        }

        if ($shouldCreateCollaborator && $orgId) {
            // Validate phone: bắt buộc và unique ở tầng ứng dụng
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
                'organization_id' => $orgId,
                'status' => 'active',
            ]);
        }

        // Reset selectedOrganizationId sau khi xử lý
        $this->selectedOrganizationId = null;
    }

    protected function getFormActions(): array {
        return [
            $this->getCreateFormAction()
                ->label('Tạo người dùng'),
            $this->getCancelFormAction()
                ->label('Hủy'),
        ];
    }
}
