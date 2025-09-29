<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy {
    public function viewAny(User $user): bool {
        return $user->hasRole(['super_admin', 'admin', 'kế toán', 'ctv', 'chủ đơn vị']);
    }

    public function view(User $user, Payment $payment): bool {
        if ($user->hasRole(['super_admin', 'admin', 'kế toán', 'chủ đơn vị'])) {
            return true;
        }
        if ($user->hasRole('ctv')) {
            return in_array($user->id, [
                $payment->primary_collaborator_id,
                $payment->sub_collaborator_id,
            ]);
        }
        return false;
    }

    public function verify(User $user, Payment $payment): bool {
        return $user->can('verify_payment') || $user->hasRole('kế toán');
    }

    public function uploadReceipt(User $user, Payment $payment): bool {
        return $user->hasRole('kế toán');
    }
}
