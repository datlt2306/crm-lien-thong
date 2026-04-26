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
                $commission = $this->commissionService->createCommissionFromPayment($payment);

                // 1. Thông báo duyệt hóa đơn cho CTV chính
                if ($payment->student && $payment->student->collaborator) {
                    $ctvUser = \App\Models\User::where('email', $payment->student->collaborator->email)->first();
                    if ($ctvUser) {
                        try {
                            $ctvUser->notify(new \App\Notifications\PaymentStatusUpdatedNotification($payment, 'verified'));
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Telegram Notification Error (Payment Verified): ' . $e->getMessage());
                        }
                    }
                }

                // 2. Thông báo nhận hoa hồng cho các CTV liên quan (Chính + Phụ)
                if ($commission) {
                    foreach ($commission->items as $item) {
                        if ($item->collaborator) {
                            $itemUser = \App\Models\User::where('email', $item->collaborator->email)->first();
                            if ($itemUser) {
                                try {
                                    $itemUser->notify(new \App\Notifications\CommissionEarnedNotification($item));
                                } catch (\Exception $e) {
                                    \Illuminate\Support\Facades\Log::error('Telegram Notification Error (Commission Earned): ' . $e->getMessage());
                                }
                            }
                        }
                    }
                }
            } 
            // Nếu CTV vừa upload bill (status chuyển sang SUBMITTED)
            elseif ($newStatus === Payment::STATUS_SUBMITTED && $oldStatus !== Payment::STATUS_SUBMITTED) {
                // Notify Super Admins and Staff who can upload receipt
                $recipients = \App\Models\User::whereIn('role', ['super_admin', 'accountant', 'document'])->get();
                foreach ($recipients as $user) {
                    try {
                        $user->notify(new \App\Notifications\PaymentBillUploadedNotification($payment));
                    } catch (\Exception $e) {
                        \Illuminate\Support\Facades\Log::error('Telegram Notification Error (Bill Uploaded): ' . $e->getMessage());
                    }
                }
            }
            // Nếu hóa đơn bị từ chối (Chuyển từ SUBMITTED về NOT_PAID)
            elseif ($newStatus === Payment::STATUS_NOT_PAID && $oldStatus === Payment::STATUS_SUBMITTED) {
                if ($payment->student && $payment->student->collaborator) {
                    $ctvUser = \App\Models\User::where('email', $payment->student->collaborator->email)->first();
                    if ($ctvUser) {
                        try {
                            $ctvUser->notify(new \App\Notifications\PaymentStatusUpdatedNotification($payment, 'rejected'));
                        } catch (\Exception $e) {
                            \Illuminate\Support\Facades\Log::error('Telegram Notification Error (Payment Rejected): ' . $e->getMessage());
                        }
                    }
                }
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
