<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\DashboardCacheService;

class PaymentObserver {
    protected function bust(): void {
        DashboardCacheService::bumpVersion();
    }

    public function created(Payment $payment): void {
        $this->bust();
    }
    public function updated(Payment $payment): void {
        $this->bust();
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
