<?php

namespace App\Observers;

use App\Models\Payment;
use App\Services\DashboardCacheService;
use App\Services\QuotaService;

class PaymentObserver {
    protected QuotaService $quotaService;
    protected \App\Services\CommissionService $commissionService;

    public function __construct(QuotaService $quotaService, \App\Services\CommissionService $commissionService) {
        $this->quotaService = $quotaService;
        $this->commissionService = $commissionService;
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

            // Nếu payment vừa được verify thì trừ quota và tạo hoa hồng
            if ($newStatus === Payment::STATUS_VERIFIED && $oldStatus !== Payment::STATUS_VERIFIED) {
                // Trừ quota khi payment được verify
                $this->quotaService->decreaseQuotaOnPaymentSubmission($payment);
                
                // Tự động tạo hoa hồng dựa trên chính sách
                $this->commissionService->createCommissionFromPayment($payment);
            } 
            // Nếu payment đang từ VERIFIED chuyển sang trạng thái khác (vd: bị hoàn hay hủy) thì cọng lại quota
            elseif ($oldStatus === Payment::STATUS_VERIFIED && $newStatus !== Payment::STATUS_VERIFIED) {
                $this->quotaService->restoreQuotaOnPaymentReverted($payment);
                // Lưu ý: Tạm thời chưa tự động hủy hoa hồng để kế toán kiểm tra thủ công cho an toàn
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
