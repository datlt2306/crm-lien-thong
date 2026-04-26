<?php

namespace App\Policies;

use App\Models\Payment;
use App\Models\User;

class PaymentPolicy {
    public function viewAny(User $user): bool {
        return $user->can('payment_view_any') || $user->hasRole(['super_admin', 'admin', 'accountant', 'document', 'collaborator']);
    }

    public function view(User $user, Payment $payment): bool {
        // Nếu có quyền xem chi tiết (được set qua UI phân quyền)
        if ($user->can('payment_view')) {
            return true;
        }

        // Mặc định cho phép Admin/Kế toán/Hồ sơ (Fallback cho các role cũ)
        if ($user->hasRole(['super_admin', 'admin', 'accountant', 'document'])) {
            return true;
        }

        // CTV chỉ được xem của mình
        if ($user->hasRole('collaborator')) {
            return in_array($user->id, [
                $payment->primary_collaborator_id,
                $payment->sub_collaborator_id,
            ]);
        }

        return false;
    }

    public function verify(User $user, Payment $payment): bool {
        return $user->can('payment_verify') || $user->hasRole(['super_admin', 'admin', 'accountant', 'document']);
    }

    public function uploadReceipt(User $user, Payment $payment): bool {
        return $user->can('payment_upload_receipt') || $user->hasRole(['super_admin', 'admin', 'accountant']);
    }
}
