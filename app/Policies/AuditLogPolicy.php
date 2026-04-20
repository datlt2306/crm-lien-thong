<?php

namespace App\Policies;

use App\Models\AuditLog;
use App\Models\User;

class AuditLogPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return in_array($user->role, ['super_admin', 'admin', 'accountant', 'admissions', 'document', 'ctv']);
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, AuditLog $auditLog): bool
    {
        if (in_array($user->role, ['super_admin', 'admin', 'accountant', 'admissions', 'document'])) {
            return true;
        }

        if ($user->role === 'ctv') {
            $collab = $user->collaborator;
            return $collab && $auditLog->student_id && $auditLog->student->collaborator_id === $collab->id;
        }

        return false;
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return false; // Logs are created via system hooks
    }

    /**
     * Determine whether the user can update the model.
     */
    public function update(User $user, AuditLog $auditLog): bool
    {
        return false; // Immutable logs
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, AuditLog $auditLog): bool
    {
        return false; // Immutable logs
    }
}
