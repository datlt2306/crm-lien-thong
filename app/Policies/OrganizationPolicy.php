<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class OrganizationPolicy {
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool {
        return in_array($user->role, ['super_admin', 'organization_owner']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Organization $organization): bool {
        // Super admin có thể xem tất cả
        if ($user->role === 'super_admin') {
            return true;
        }

        // Chủ đơn vị chỉ có thể xem đơn vị của mình
        if ($user->role === 'organization_owner') {
            return $organization->organization_owner_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, Organization $organization): bool {
        // Super admin có thể cập nhật tất cả
        if ($user->role === 'super_admin') {
            return true;
        }

        // Chủ đơn vị chỉ có thể cập nhật đơn vị của mình
        if ($user->role === 'organization_owner') {
            return $organization->organization_owner_id === $user->id;
        }

        return false;
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Organization $organization): bool {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Organization $organization): bool {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Organization $organization): bool {
        return $user->role === 'super_admin';
    }
}
