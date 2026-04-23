<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Gate;
use App\Services\CollaboratorValidationService;

class CreateUser extends CreateRecord {
    protected static string $resource = UserResource::class;

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
        // Tự động verify email cho user mới tạo
        $data['email_verified_at'] = now();

        // Nếu chưa có role được chọn, mặc định là 'ctv'
        if (empty($data['role'])) {
            $data['role'] = 'ctv';
        }

        return $data;
    }

    protected function handleRecordCreation(array $data): \Illuminate\Database\Eloquent\Model {
        $user = parent::handleRecordCreation($data);

        // Upload avatar logic remains here
        if (isset($data['avatar']) && request()->hasFile('components.avatar')) {
            $file = request()->file('components.avatar');
            $path = $file->store('avatars', 'public');
            $user->update(['avatar' => $path]);
        }

        // Đồng bộ collaborator cho user CTV trong mô hình single-organization.
        if (($user->role ?? null) === 'ctv') {
            CollaboratorValidationService::validateForCreation($user->email, $user->phone);
            \App\Models\Collaborator::updateOrCreate(
                ['email' => $user->email],
                [
                    'full_name' => $user->name,
                    'phone' => $user->phone,
                    'status' => 'active',
                ]
            );
        }

        return $user;
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
