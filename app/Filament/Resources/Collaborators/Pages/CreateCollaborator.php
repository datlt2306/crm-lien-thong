<?php

namespace App\Filament\Resources\Collaborators\Pages;

use App\Filament\Resources\Collaborators\CollaboratorResource;
use App\Models\User;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use App\Models\Organization;

class CreateCollaborator extends CreateRecord {
    protected static string $resource = CollaboratorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array {
        $user = Auth::user();
        if ($user->role === 'super_admin') {
            // Nếu là super_admin, cho phép chọn organization_id và upline_id (không làm gì)
        } else {
            // Nếu là chủ tổ chức hoặc CTV, tự động gán organization_id và upline_id
            $org = Organization::where('owner_id', $user->id)->first();
            if ($org) {
                $data['organization_id'] = $org->id;
            } else {
                // Nếu không phải chủ tổ chức, tìm collaborator của user hiện tại
                $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
                if ($collaborator) {
                    $data['organization_id'] = $collaborator->organization_id;
                    // Tự động gán upline_id là CTV hiện tại
                    $data['upline_id'] = $collaborator->id;
                }
            }
        }

        // Tạo User account cho collaborator
        if (!empty($data['email'])) {
            $password = !empty($data['password']) ? $data['password'] : '123456';

            $userAccount = User::create([
                'name' => $data['full_name'],
                'email' => $data['email'],
                'password' => Hash::make($password),
                'role' => 'ctv',
            ]);

            // Gán role 'ctv' cho collaborator
            $userAccount->assignRole('ctv');

            // Cập nhật organization owner_id nếu chưa có
            if (isset($data['organization_id'])) {
                $org = Organization::find($data['organization_id']);
                if ($org && !$org->owner_id) {
                    $org->update(['owner_id' => $userAccount->id]);
                }
            }
        }

        // Loại bỏ password khỏi data trước khi tạo Collaborator
        unset($data['password'], $data['password_confirmation']);

        return $data;
    }

    public function getTitle(): string {
        return 'Thêm CTV con mới';
    }

    public function getBreadcrumb(): string {
        return 'Thêm CTV con mới';
    }
}
