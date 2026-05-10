<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use App\Models\AnnualQuota;
use App\Models\Intake;
use App\Models\Quota;
use App\Models\Payment;
use App\Models\Student;

class QuotaService {

    /**
     * Giảm quota khi payment verified. Chuyển từ PENDING sang CURRENT.
     */
    public function consumeQuotaOnPaymentVerified(Payment $payment): bool {
        $student = $payment->student;
        if (!$student || !$student->quota_id) {
            return false;
        }

        try {
            DB::beginTransaction();

            $quota = Quota::lockForUpdate()->find($student->quota_id);
            if (!$quota) {
                DB::rollBack();
                return false;
            }

            // Chuyển từ pending sang current
            $quota->incrementCurrentQuota();

            // Cập nhật AnnualQuota tương ứng (nếu có)
            $year = (int) ($student->intake?->start_date?->format('Y') ?? now()->format('Y'));
            $annual = AnnualQuota::query()
                ->where('year', $year);

            if ($quota->major_name) {
                $annual->where('major_name', $quota->major_name);
            }
            if ($quota->program_name) {
                $annual->where('program_name', $quota->program_name);
            }

            $annual = $annual->lockForUpdate()->first();
            if ($annual) {
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
     * Chuyển từ CURRENT về PENDING.
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
            $quota->incrementPendingQuota();

            $year = (int) ($student->intake?->start_date?->format('Y') ?? now()->format('Y'));
            $annual = AnnualQuota::query()
                ->where('year', $year);

            if ($quota->major_name) {
                $annual->where('major_name', $quota->major_name);
            }
            if ($quota->program_name) {
                $annual->where('program_name', $quota->program_name);
            }

            $annual = $annual->lockForUpdate()->first();

            if ($annual && $annual->current_quota > 0) {
                $annual->decrementCurrent();
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

    /**
     * Xử lý khi có sinh viên đăng ký mới
     */
    public function handleStudentRegistration(Student $student): void {
        if (!$student->quota_id) return;

        $quota = Quota::find($student->quota_id);
        if ($quota) {
            $quota->incrementPendingQuota();
        }
    }

    /**
     * Xử lý khi sinh viên chuyển ngành/đợt (quota_id thay đổi)
     */
    public function handleStudentTransfer(Student $student, ?int $oldQuotaId, ?int $newQuotaId): void {
        if ($oldQuotaId === $newQuotaId) return;

        // Nếu sinh viên đang ở trạng thái hủy/từ chối thì không cần cập nhật quota (vì đã được giải phóng hoặc chưa chiếm)
        if (in_array($student->status, [Student::STATUS_REJECTED, Student::STATUS_DROPPED])) {
            return;
        }

        try {
            DB::beginTransaction();

            // 1. Trả lại quota cũ
            if ($oldQuotaId) {
                $oldQuota = Quota::lockForUpdate()->find($oldQuotaId);
                if ($oldQuota) {
                    if ($student->payment && $student->payment->status === Payment::STATUS_VERIFIED) {
                        $oldQuota->decrementCurrentQuota();
                        $this->decrementAnnualQuota($student, $oldQuota);
                    } else {
                        $oldQuota->decrementPendingQuota();
                    }
                }
            }

            // 2. Chiếm quota mới
            if ($newQuotaId) {
                $newQuota = Quota::lockForUpdate()->find($newQuotaId);
                if ($newQuota) {
                    if ($student->payment && $student->payment->status === Payment::STATUS_VERIFIED) {
                        $newQuota->incrementCurrentQuota();
                        $this->incrementAnnualQuota($student, $newQuota);
                    } else {
                        $newQuota->incrementPendingQuota();
                    }
                }
            }

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('QuotaService::handleStudentTransfer', [
                'student_id' => $student->id,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Xử lý khi sinh viên bị xóa hoặc hủy hồ sơ
     */
    public function handleStudentCancellation(Student $student): void {
        if (!$student->quota_id) return;

        $quota = Quota::find($student->quota_id);
        if (!$quota) return;

        if ($student->payment && $student->payment->status === Payment::STATUS_VERIFIED) {
            $quota->decrementCurrentQuota();
            $this->decrementAnnualQuota($student, $quota);
        } else {
            $quota->decrementPendingQuota();
        }
    }

    private function incrementAnnualQuota(Student $student, Quota $quota): void {
        $year = (int) ($student->intake?->start_date?->format('Y') ?? now()->format('Y'));
        $annual = AnnualQuota::where('year', $year)
            ->where('major_name', $quota->major_name)
            ->where('program_name', $quota->program_name)
            ->first();
        
        if ($annual) {
            $annual->incrementCurrent();
        }
    }

    private function decrementAnnualQuota(Student $student, Quota $quota): void {
        $year = (int) ($student->intake?->start_date?->format('Y') ?? now()->format('Y'));
        $annual = AnnualQuota::where('year', $year)
            ->where('major_name', $quota->major_name)
            ->where('program_name', $quota->program_name)
            ->first();
        
        if ($annual && $annual->current_quota > 0) {
            $annual->decrementCurrent();
        }
    }

    public function decreaseQuotaOnPaymentSubmission(Payment $payment): bool {
        return $this->consumeQuotaOnPaymentVerified($payment);
    }
}
