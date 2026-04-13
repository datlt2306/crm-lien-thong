<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\AnnualQuota;
use App\Models\Intake;
use App\Models\Quota;
use App\Models\Payment;

class QuotaService {

    /**
     * Giảm quota khi payment verified. Dùng quotas/annual_quotas (org, major_name, program_name, year).
     */
    public function consumeQuotaOnPaymentVerified(Payment $payment): bool {
        $student = $payment->student;
        if (!$student || !$student->quota_id) {
            return false;
        }

        try {
            DB::beginTransaction();

            $quota = Quota::lockForUpdate()->find($student->quota_id);
            if (!$quota || !$quota->hasAvailableSlots()) {
                DB::rollBack();
                return false;
            }

            $quota->incrementCurrentQuota();

            // Cập nhật AnnualQuota tương ứng (nếu có)
            $year = (int) ($student->intake?->start_date?->format('Y') ?? now()->format('Y'));
            $annual = AnnualQuota::query()
                ->where('organization_id', $payment->organization_id)
                ->where('year', $year);

            if ($quota->major_name) {
                $annual->where('major_name', $quota->major_name);
            }
            if ($quota->program_name) {
                $annual->where('program_name', $quota->program_name);
            }

            $annual = $annual->lockForUpdate()->first();

            if ($annual && $annual->hasAvailableSlots()) {
                $annual->incrementCurrent();
            }

            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('QuotaService::consumeQuotaOnPaymentVerified', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Hoàn trả chỉ tiêu khi Payment bị hủy hoặc reject (từ VERIFIED sang trạng thái khác)
     */
    public function restoreQuotaOnPaymentReverted(Payment $payment): bool {
        $student = $payment->student;
        if (!$student || !$student->quota_id) {
            return false;
        }

        try {
            DB::beginTransaction();

            $quota = Quota::lockForUpdate()->find($student->quota_id);
            if (!$quota || $quota->current_quota <= 0) {
                DB::rollBack();
                return false;
            }

            $quota->decrementCurrentQuota();

            $year = (int) ($student->intake?->start_date?->format('Y') ?? now()->format('Y'));
            $annual = AnnualQuota::query()
                ->where('organization_id', $payment->organization_id)
                ->where('year', $year);

            if ($quota->major_name) {
                $annual->where('major_name', $quota->major_name);
            }
            if ($quota->program_name) {
                $annual->where('program_name', $quota->program_name);
            }

            $annual = $annual->lockForUpdate()->first();

            if ($annual && $annual->current_quota > 0) {
                $annual->decrement('current_quota');
            }

            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('QuotaService::restoreQuotaOnPaymentReverted', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    public function decreaseQuotaOnPaymentSubmission(Payment $payment): bool {
        return $this->consumeQuotaOnPaymentVerified($payment);
    }
}
