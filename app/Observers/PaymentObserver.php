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

        // Kiểm tra nếu payment vừa được verify thì trừ quota
        if (
            $payment->isDirty('status') &&
            $payment->status === Payment::STATUS_VERIFIED &&
            $payment->getOriginal('status') !== Payment::STATUS_VERIFIED
        ) {

            // Trừ quota khi payment được verify
            $this->quotaService->decreaseQuotaOnPaymentSubmission($payment);
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
