<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy {
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool {
        return in_array($user->role, ['super_admin', 'organization_owner']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, $model): bool {
        if ($user->role === 'super_admin') return true;

        // Owner có thể xem user trong tổ chức của mình
        if ($user->role === 'organization_owner') {
            $org = \App\Models\Organization::where('organization_owner_id', $user->id)->first();
            if ($org) {
                // Kiểm tra xem user có phải là collaborator trong tổ chức không
                $collaborator = \App\Models\Collaborator::where('email', $model->email)
                    ->where('organization_id', $org->id)
                    ->first();
                return $collaborator !== null;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool {
        return in_array($user->role, ['super_admin', 'organization_owner']);
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, $model): bool {
        if ($user->role === 'super_admin') return true;

        // Owner có thể cập nhật user trong tổ chức của mình
        if ($user->role === 'organization_owner') {
            $org = \App\Models\Organization::where('organization_owner_id', $user->id)->first();
            if ($org) {
                // Kiểm tra xem user có phải là collaborator trong tổ chức không
                $collaborator = \App\Models\Collaborator::where('email', $model->email)
                    ->where('organization_id', $org->id)
                    ->first();
                return $collaborator !== null;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, $model): bool {
        if ($user->role === 'super_admin') return true;

        // Owner có thể xóa user trong tổ chức của mình (trừ chính mình)
        if ($user->role === 'organization_owner' && $user->id !== $model->id) {
            $org = \App\Models\Organization::where('organization_owner_id', $user->id)->first();
            if ($org) {
                // Kiểm tra xem user có phải là collaborator trong tổ chức không
                $collaborator = \App\Models\Collaborator::where('email', $model->email)
                    ->where('organization_id', $org->id)
                    ->first();
                return $collaborator !== null;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, $model): bool {
        if ($user->role === 'super_admin') return true;

        // Owner có thể restore user trong tổ chức của mình
        if ($user->role === 'organization_owner') {
            $org = \App\Models\Organization::where('organization_owner_id', $user->id)->first();
            if ($org) {
                // Kiểm tra xem user có phải là collaborator trong tổ chức không
                $collaborator = \App\Models\Collaborator::where('email', $model->email)
                    ->where('organization_id', $org->id)
                    ->first();
                return $collaborator !== null;
            }
        }

        return false;
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, $model): bool {
        if ($user->role === 'super_admin') return true;

        // Owner có thể force delete user trong tổ chức của mình (trừ chính mình)
        if ($user->role === 'organization_owner' && $user->id !== $model->id) {
            $org = \App\Models\Organization::where('organization_owner_id', $user->id)->first();
            if ($org) {
                // Kiểm tra xem user có phải là collaborator trong tổ chức không
                $collaborator = \App\Models\Collaborator::where('email', $model->email)
                    ->where('organization_id', $org->id)
                    ->first();
                return $collaborator !== null;
            }
        }

        return false;
    }
}
