<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy {
    public function viewAny(User $user): bool {
        return $user->hasRole(['super_admin', 'admin', 'accountant', 'ctv', 'document']);
    }

    public function view(User $user, Payment $payment): bool {
        if ($user->hasRole(['super_admin', 'admin', 'accountant', 'document'])) {
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
        // Kế toán và cán bộ hồ sơ đều có thể xác nhận số tiền sinh viên nộp đăng ký
        return $user->can('verify_payment') || $user->hasRole(['accountant', 'document']);
    }

    public function uploadReceipt(User $user, Payment $payment): bool {
        return $user->hasRole('accountant');
    }
}
