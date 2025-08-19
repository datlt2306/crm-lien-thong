<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

class UserPolicy {
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny($user): bool {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view($user, $model): bool {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can create models.
     */
    public function create($user): bool {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update($user, $model): bool {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete($user, $model): bool {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore($user, $model): bool {
        return $user->role === 'super_admin';
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete($user, $model): bool {
        return $user->role === 'super_admin';
    }
}
