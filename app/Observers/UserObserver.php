<?php

namespace App\Observers;

use App\Models\User;
use App\Models\Collaborator;

class UserObserver {
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void {
        // Tự động tạo mối quan hệ khi tạo user mới
        

        // 2. Tạo Collaborator record chỉ cho CTV
        if ($user->role === 'collaborator') {
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
        } elseif ($newRole === 'collaborator') {
            // Nếu người này là CTV và role không đổi, update thông tin (tên, sđt, tổ chức)
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                $updates = [];
                if ($user->isDirty('name')) $updates['full_name'] = $user->name;
                if ($user->isDirty('phone')) $updates['phone'] = $user->phone;
                
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
                'status' => 'active'
            ]);
        }
    }

    /**
     * Cập nhật Collaborator record khi role thay đổi
     */
    private function updateCollaboratorRecord(User $user, string $oldRole, string $newRole): void {
        $collaborator = Collaborator::where('email', $user->email)->first();

        if ($collaborator) {
            // Nếu chuyển từ role ctv sang role khác
            if ($oldRole === 'collaborator' && $newRole !== 'collaborator') {
                $collaborator->delete();
            }
        } else {
            // Nếu chuyển sang role ctv (không tạo cho organization_owner)
            if ($newRole === 'collaborator') {
                $this->createCollaboratorRecord($user);
            }
        }
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void {
        // Khi xóa user, xóa các mối quan hệ liên quan


        // 2. Xóa Collaborator record
        $collaborators = Collaborator::where('email', $user->email)->get();
        foreach ($collaborators as $collaborator) {
            $collaborator->delete();
        }
    }

}
