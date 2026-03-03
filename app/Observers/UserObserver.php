<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Organization;
use App\Models\Collaborator;

class UserObserver {
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void {
        // Tự động tạo mối quan hệ khi tạo user mới
        
        // 1. Nếu là organization_owner, ưu tiên tổ chức được chọn hoặc tìm tự do chưa có chủ
        if ($user->role === 'organization_owner') {
            if ($user->organization_id) {
                $org = Organization::find($user->organization_id);
                if ($org) {
                    $org->update(['organization_owner_id' => $user->id]);
                }
            } else {
                $organizationWithoutOwner = Organization::whereNull('organization_owner_id')->first();
                if ($organizationWithoutOwner) {
                    $organizationWithoutOwner->update(['organization_owner_id' => $user->id]);
                    $user->updateQuietly(['organization_id' => $organizationWithoutOwner->id]);
                }
            }
        }

        // 2. Tạo Collaborator record chỉ cho CTV
        if ($user->role === 'ctv') {
            $this->createCollaboratorRecord($user);
        }

        // 3. Đồng bộ hóa với Spatie Role để User mới thao tác được Permission
        if (!empty($user->role)) {
            $roleExists = \Spatie\Permission\Models\Role::where('name', $user->role)->exists();
            if ($roleExists && method_exists($user, 'assignRole')) {
                $user->assignRole($user->role);
            }
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void {
        $oldRole = $user->getOriginal('role');
        $newRole = $user->role;
        $oldOrgId = $user->getOriginal('organization_id');
        $newOrgId = $user->organization_id;

        // Xử lý thay đổi role liên quan đến organization_owner
        if ($oldRole === 'organization_owner' && $newRole !== 'organization_owner') {
            Organization::where('organization_owner_id', $user->id)->update(['organization_owner_id' => null]);
        }

        if ($newRole === 'organization_owner') {
            if ($newOrgId) {
                // Bỏ owner của tổ chức cũ (nếu có)
                Organization::where('organization_owner_id', $user->id)->where('id', '!=', $newOrgId)->update(['organization_owner_id' => null]);
                // Set owner cho tổ chức mới
                Organization::where('id', $newOrgId)->update(['organization_owner_id' => $user->id]);
            }
        }

        // Khi role thay đổi, cập nhật mối quan hệ Role/Collaborator
        if ($user->isDirty('role')) {
            // Cập nhật Collaborator record
            $this->updateCollaboratorRecord($user, $oldRole, $newRole);

            // Đồng bộ lại Spatie Role khi user bị đổi chức danh
            if (!empty($newRole)) {
                $roleExists = \Spatie\Permission\Models\Role::where('name', $newRole)->exists();
                if ($roleExists && method_exists($user, 'syncRoles')) {
                    $user->syncRoles([$newRole]);
                }
            } else {
                if (method_exists($user, 'syncRoles')) {
                    $user->syncRoles([]);
                }
            }
        } elseif ($newRole === 'ctv') {
            // Nếu người này là CTV và role không đổi, update thông tin (tên, sđt, tổ chức)
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                $updates = [];
                if ($user->isDirty('name')) $updates['full_name'] = $user->name;
                if ($user->isDirty('phone')) $updates['phone'] = $user->phone;
                if ($user->isDirty('organization_id') && $newOrgId) $updates['organization_id'] = $newOrgId;
                
                if (!empty($updates)) {
                    $collaborator->update($updates);
                }
            }
        }
    }

    /**
     * Tạo Collaborator record cho user
     */
    private function createCollaboratorRecord(User $user): void {
        // Kiểm tra xem đã có Collaborator chưa
        $existingCollaborator = Collaborator::where('email', $user->email)->first();

        if (!$existingCollaborator) {
            $organization = $this->getUserOrganization($user);

            if ($organization) {
                // Xử lý phone NULL - tạo phone từ email hoặc sử dụng default
                $phone = $user->phone;
                if (empty($phone)) {
                    // Tạo phone từ email hoặc sử dụng ID
                    $phone = '0000000000'; // Default phone
                    if (!empty($user->email)) {
                        $phone = '000' . substr(preg_replace('/[^0-9]/', '', $user->email), 0, 7);
                    }
                }

                // Kiểm tra phone trùng lặp
                $existingPhone = Collaborator::where('phone', $phone)->first();
                if ($existingPhone) {
                    $phone = $phone . '_' . time();
                }

                Collaborator::create([
                    'full_name' => $user->name,
                    'email' => $user->email,
                    'phone' => $phone,
                    'organization_id' => $organization->id,
                    'status' => 'active'
                ]);
            }
        }
    }

    /**
     * Cập nhật Collaborator record khi role thay đổi
     */
    private function updateCollaboratorRecord(User $user, string $oldRole, string $newRole): void {
        $collaborator = Collaborator::where('email', $user->email)->first();

        if ($collaborator) {
            // Nếu chuyển từ role ctv sang role khác (hoặc chuyển từ organization_owner sang role khác)
            if (($oldRole === 'ctv' && $newRole !== 'ctv') || ($oldRole === 'organization_owner' && $newRole !== 'organization_owner')) {
                $collaborator->delete();
            }
        } else {
            // Nếu chuyển sang role ctv (không tạo cho organization_owner)
            if ($newRole === 'ctv') {
                $this->createCollaboratorRecord($user);
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void {
        // Khi xóa user, xóa các mối quan hệ liên quan

        // 1. Nếu là organization_owner, xóa organization_owner_id
        $organizations = Organization::where('organization_owner_id', $user->id)->get();
        foreach ($organizations as $organization) {
            $organization->update(['organization_owner_id' => null]);
        }

        // 2. Xóa Collaborator record
        $collaborators = Collaborator::where('email', $user->email)->get();
        foreach ($collaborators as $collaborator) {
            $collaborator->delete();
        }
    }

    /**
     * Lấy organization phù hợp cho user
     */
    private function getUserOrganization(User $user): ?Organization {
        if (!empty($user->organization_id)) {
            return Organization::find($user->organization_id);
        }

        // Nếu là organization_owner, tìm organization của họ
        if ($user->role === 'organization_owner') {
            return Organization::where('organization_owner_id', $user->id)->first();
        }

        return null;
    }
}
