<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Student;
use App\Models\Payment;

class QuotaService {
    /**
     * Giảm quota của ngành khi học viên đăng ký thành công
     */
    public function decreaseQuotaOnStudentRegistration(Student $student): bool {
        if (!$student->organization_id || !$student->major) {
            return false;
        }

        try {
            DB::beginTransaction();

            // Tìm major_id từ tên major
            $major = DB::table('majors')
                ->where('name', $student->major)
                ->first();

            if (!$major) {
                DB::rollBack();
                return false;
            }

            // Kiểm tra và giảm quota
            $quotaRecord = DB::table('major_organization')
                ->where('organization_id', $student->organization_id)
                ->where('major_id', $major->id)
                ->lockForUpdate() // Lock để tránh race condition
                ->first();

            if (!$quotaRecord || $quotaRecord->quota <= 0) {
                DB::rollBack();
                return false;
            }

            // Giảm quota đi 1
            DB::table('major_organization')
                ->where('organization_id', $student->organization_id)
                ->where('major_id', $major->id)
                ->update(['quota' => $quotaRecord->quota - 1]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('QuotaService::decreaseQuotaOnStudentRegistration error', [
                'student_id' => $student->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Giảm quota của ngành khi học viên nộp tiền thành công
     */
    public function decreaseQuotaOnPaymentSubmission(Payment $payment): bool {
        if (!$payment->student || !$payment->student->major) {
            return false;
        }

        try {
            DB::beginTransaction();

            // Tìm major_id từ tên major
            $major = DB::table('majors')
                ->where('name', $payment->student->major)
                ->first();

            if (!$major) {
                DB::rollBack();
                return false;
            }

            // Kiểm tra và giảm quota
            $quotaRecord = DB::table('major_organization')
                ->where('organization_id', $payment->organization_id)
                ->where('major_id', $major->id)
                ->lockForUpdate() // Lock để tránh race condition
                ->first();

            if (!$quotaRecord || $quotaRecord->quota <= 0) {
                DB::rollBack();
                return false;
            }

            // Giảm quota đi 1
            DB::table('major_organization')
                ->where('organization_id', $payment->organization_id)
                ->where('major_id', $major->id)
                ->update(['quota' => $quotaRecord->quota - 1]);

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('QuotaService::decreaseQuotaOnPaymentSubmission error', [
                'payment_id' => $payment->id,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Kiểm tra xem ngành còn quota không
     */
    public function hasQuota(int $organizationId, int $majorId): bool {
        $quotaRecord = DB::table('major_organization')
            ->where('organization_id', $organizationId)
            ->where('major_id', $majorId)
            ->first();

        return $quotaRecord && $quotaRecord->quota > 0;
    }

    /**
     * Lấy quota hiện tại của ngành
     */
    public function getCurrentQuota(int $organizationId, int $majorId): int {
        $quotaRecord = DB::table('major_organization')
            ->where('organization_id', $organizationId)
            ->where('major_id', $majorId)
            ->first();

        return $quotaRecord ? $quotaRecord->quota : 0;
    }
}
