<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\AnnualQuota;
use App\Models\Intake;
use App\Models\Quota;
use App\Models\Payment;

/**
 * Chỉ tiêu năm (annual): 1 năm có target (vd 100 CNTT chính quy), chia linh hoạt cho nhiều đợt.
 * Đợt 1 đủ 100 → hết; đợt 1 chỉ 30 → 70 chuyển đợt sau.
 */
class QuotaService {
    /**
     * Giảm quota major_organization khi đăng ký (legacy, dùng khi chưa có annual).
     */
    public function decreaseQuotaOnStudentRegistration(\App\Models\Student $student): bool {
        if (!$student->organization_id || !$student->major) {
            return false;
        }
        try {
            DB::beginTransaction();
            $major = DB::table('majors')->where('name', $student->major)->first();
            if (!$major) {
                DB::rollBack();
                return false;
            }
            $rec = DB::table('major_organization')
                ->where('organization_id', $student->organization_id)
                ->where('major_id', $major->id)
                ->lockForUpdate()
                ->first();
            if (!$rec || $rec->quota <= 0) {
                DB::rollBack();
                return false;
            }
            DB::table('major_organization')
                ->where('organization_id', $student->organization_id)
                ->where('major_id', $major->id)
                ->update(['quota' => $rec->quota - 1]);
            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('QuotaService::decreaseQuotaOnStudentRegistration', ['student_id' => $student->id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Giảm quota major_organization khi payment verified (legacy). 
     * Ưu tiên dùng consumeQuotaOnPaymentVerified (annual).
     */
    public function decreaseQuotaOnPaymentSubmission(Payment $payment): bool {
        $consumed = $this->consumeQuotaOnPaymentVerified($payment);
        if ($consumed) {
            return true;
        }
        return $this->decreaseQuotaOnPaymentSubmissionLegacy($payment);
    }

    private function decreaseQuotaOnPaymentSubmissionLegacy(Payment $payment): bool {
        if (!$payment->student) {
            return false;
        }
        $student = $payment->student;
        $major = DB::table('majors')->where('name', $student->major)->first();
        if (!$major) {
            return false;
        }
        try {
            DB::beginTransaction();
            $rec = DB::table('major_organization')
                ->where('organization_id', $payment->organization_id)
                ->where('major_id', $major->id)
                ->lockForUpdate()
                ->first();
            if (!$rec || $rec->quota <= 0) {
                DB::rollBack();
                return false;
            }
            DB::table('major_organization')
                ->where('organization_id', $payment->organization_id)
                ->where('major_id', $major->id)
                ->update(['quota' => $rec->quota - 1]);
            DB::commit();
            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('QuotaService::decreaseQuotaOnPaymentSubmissionLegacy', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            return false;
        }
    }

    /**
     * Trừ chỉ tiêu năm khi payment được verify. Dùng annual_quotas (org, major, program, year).
     */
    public function consumeQuotaOnPaymentVerified(Payment $payment): bool {
        $student = $payment->student;
        if (!$student) {
            return false;
        }
        $majorId = $student->major_id;
        if (!$majorId) {
            $major = DB::table('majors')->where('name', $student->major)->first();
            $majorId = $major?->id;
        }
        if (!$majorId) {
            return false;
        }
        $programId = $student->program_id;
        if (!$programId) {
            Log::warning('QuotaService::consumeQuotaOnPaymentVerified: student không có program_id', ['student_id' => $student->id]);
            return false;
        }
        $year = (int) ($student->intake?->start_date?->format('Y') ?? now()->format('Y'));

        if (!Schema::hasTable('annual_quotas')) {
            return false;
        }

        try {
            DB::beginTransaction();
            $annual = AnnualQuota::query()
                ->where('organization_id', $payment->organization_id)
                ->where('major_id', $majorId)
                ->where('program_id', $programId)
                ->where('year', $year)
                ->where('status', AnnualQuota::STATUS_ACTIVE)
                ->lockForUpdate()
                ->first();

            if (!$annual || !$annual->hasAvailableSlots()) {
                DB::rollBack();
                return false;
            }
            $annual->incrementCurrent();
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
     * Kiểm tra còn chỉ tiêu. Nếu có programId: kiểm tra (org, major, hệ); không thì xét tổng ngành.
     */
    public function hasQuota(int $organizationId, int $majorId, ?int $programId = null): bool {
        if ($programId !== null) {
            return $this->getCurrentQuotaForProgram($organizationId, $majorId, $programId) > 0;
        }
        return $this->getCurrentQuota($organizationId, $majorId) > 0;
    }

    /**
     * Chỉ tiêu còn trống cho (org, major, program, năm hiện tại).
     */
    public function getCurrentQuotaForProgram(int $organizationId, int $majorId, int $programId, ?int $year = null): int {
        $year = $year ?? (int) now()->format('Y');
        if (!Schema::hasTable('annual_quotas')) {
            return 0;
        }
        $row = DB::table('annual_quotas')
            ->where('organization_id', $organizationId)
            ->where('major_id', $majorId)
            ->where('program_id', $programId)
            ->where('year', $year)
            ->where('status', AnnualQuota::STATUS_ACTIVE)
            ->first();
        if (!$row) {
            return 0;
        }
        return max(0, (int) $row->target_quota - (int) $row->current_quota);
    }

    /**
     * Tổng chỉ tiêu còn trống của ngành (tổng các hệ) trong năm. Ưu tiên annual_quotas; fallback quotas theo đợt rồi major_organization.
     */
    public function getCurrentQuota(int $organizationId, int $majorId, ?int $year = null): int {
        $year = $year ?? (int) now()->format('Y');

        if (Schema::hasTable('annual_quotas')) {
            $total = (int) DB::table('annual_quotas')
                ->where('organization_id', $organizationId)
                ->where('major_id', $majorId)
                ->where('year', $year)
                ->where('status', AnnualQuota::STATUS_ACTIVE)
                ->selectRaw('COALESCE(SUM(CASE WHEN target_quota > current_quota THEN target_quota - current_quota ELSE 0 END), 0) as t')
                ->value('t');
            if ($total > 0) {
                return $total;
            }
        }

        if (Schema::hasTable('quotas') && Schema::hasTable('intakes')) {
            $fromQuotas = (int) DB::table('quotas')
                ->join('intakes', 'quotas.intake_id', '=', 'intakes.id')
                ->where('quotas.organization_id', $organizationId)
                ->where('quotas.major_id', $majorId)
                ->where('intakes.end_date', '>=', now()->toDateString())
                ->where('quotas.status', Quota::STATUS_ACTIVE)
                ->where('intakes.status', Intake::STATUS_ACTIVE)
                ->selectRaw('COALESCE(SUM(CASE WHEN quotas.target_quota > quotas.current_quota THEN quotas.target_quota - quotas.current_quota ELSE 0 END), 0) as t')
                ->value('t');
            if ($fromQuotas > 0) {
                return $fromQuotas;
            }
        }

        $rec = DB::table('major_organization')
            ->where('organization_id', $organizationId)
            ->where('major_id', $majorId)
            ->first();
        return $rec ? (int) $rec->quota : 0;
    }

    /**
     * Chỉ tiêu còn trống từ quotas theo đợt (legacy, dùng khi chưa có annual).
     */
    public function getAvailableSlotsFromQuotas(int $organizationId, int $majorId): int {
        if (!Schema::hasTable('quotas') || !Schema::hasTable('intakes')) {
            return 0;
        }
        return (int) DB::table('quotas')
            ->join('intakes', 'quotas.intake_id', '=', 'intakes.id')
            ->where('quotas.organization_id', $organizationId)
            ->where('quotas.major_id', $majorId)
            ->where('intakes.end_date', '>=', now()->toDateString())
            ->where('quotas.status', Quota::STATUS_ACTIVE)
            ->where('intakes.status', Intake::STATUS_ACTIVE)
            ->selectRaw('COALESCE(SUM(CASE WHEN quotas.target_quota > quotas.current_quota THEN quotas.target_quota - quotas.current_quota ELSE 0 END), 0) as t')
            ->value('t');
    }
}
