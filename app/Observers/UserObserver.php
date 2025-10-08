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

        // 1. Nếu là organization_owner, gán cho organization chưa có owner
        if ($user->role === 'organization_owner') {
            $organizationWithoutOwner = Organization::whereNull('organization_owner_id')->first();
            if ($organizationWithoutOwner) {
                $organizationWithoutOwner->update(['organization_owner_id' => $user->id]);
            }
        }

        // 2. Tạo Collaborator record chỉ cho CTV (không tạo cho organization_owner)
        if ($user->role === 'ctv') {
            $this->createCollaboratorRecord($user);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void {
        // Khi role thay đổi, cập nhật mối quan hệ
        if ($user->isDirty('role')) {
            $oldRole = $user->getOriginal('role');
            $newRole = $user->role;

            // Nếu chuyển từ organization_owner sang role khác
            if ($oldRole === 'organization_owner' && $newRole !== 'organization_owner') {
                $organization = Organization::where('organization_owner_id', $user->id)->first();
                if ($organization) {
                    $organization->update(['organization_owner_id' => null]);
                }
            }

            // Nếu chuyển sang organization_owner
            if ($newRole === 'organization_owner') {
                $organizationWithoutOwner = Organization::whereNull('organization_owner_id')->first();
                if ($organizationWithoutOwner) {
                    $organizationWithoutOwner->update(['organization_owner_id' => $user->id]);
                }
            }

            // Cập nhật Collaborator record
            $this->updateCollaboratorRecord($user, $oldRole, $newRole);
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
                    'upline_id' => null, // CTV cấp 1
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
        // Nếu là organization_owner, tìm organization của họ
        if ($user->role === 'organization_owner') {
            return Organization::where('organization_owner_id', $user->id)->first();
        }

        // Nếu là ctv, tìm organization đầu tiên (hoặc có thể mở rộng logic)
        return Organization::first();
    }
}
