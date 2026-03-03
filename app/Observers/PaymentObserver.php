<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\DashboardCacheService;
use App\Services\QuotaService;

class PaymentObserver {
    protected QuotaService $quotaService;

    public function __construct(QuotaService $quotaService) {
        $this->quotaService = $quotaService;
    }

    protected function bust(): void {
        DashboardCacheService::bumpVersion();
    }

    public function created(Payment $payment): void {
        $this->bust();
    }

    public function updated(Payment $payment): void {
        $this->bust();

        // Kiểm tra thay đổi trạng thái
        if ($payment->isDirty('status')) {
            $oldStatus = $payment->getOriginal('status');
            $newStatus = $payment->status;

            // Nếu payment vừa được verify thì trừ quota
            if ($newStatus === Payment::STATUS_VERIFIED && $oldStatus !== Payment::STATUS_VERIFIED) {
                // Trừ quota khi payment được verify
                $this->quotaService->decreaseQuotaOnPaymentSubmission($payment);
            } 
            // Nếu payment đang từ VERIFIED chuyển sang trạng thái khác (vd: bị hoàn hay hủy) thì cọng lại quota
            elseif ($oldStatus === Payment::STATUS_VERIFIED && $newStatus !== Payment::STATUS_VERIFIED) {
                $this->quotaService->restoreQuotaOnPaymentReverted($payment);
            }
        }
    }

    public function deleted(Payment $payment): void {
        $this->bust();
    }
    public function restored(Payment $payment): void {
        $this->bust();
    }
    public function forceDeleted(Payment $payment): void {
        $this->bust();
    }
}
