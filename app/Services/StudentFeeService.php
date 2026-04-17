<?php

namespace App\Services;

use App\Models\Intake;
use App\Models\Quota;
use App\Models\Student;

class StudentFeeService {
    /**
     * Tính "số tiền theo cấu hình" cho 1 sinh viên dựa trên:
     * - quota_id (nếu có)
     * - fallback: major_name, intake_id
     *
     * Hiện tại cấu hình số tiền đang nằm ở `quotas.tuition_fee`.
     */
    public function getExpectedFeeForStudent(Student $student): ?float {

        // Ưu tiên cao nhất: Lấy từ quota_id đã lưu
        if (!empty($student->quota_id)) {
            $fee = Quota::where('id', $student->quota_id)->value('tuition_fee');
            return $this->formatFee($fee);
        }

        $majorName = (string) ($student->major ?? '');
        if ($majorName === '') {
            return null;
        }

        $intakeId = $this->resolveIntakeId($student);
        if (!$intakeId) {
            return null;
        }

        $fee = Quota::query()
            ->where('major_name', $majorName)
            ->where('intake_id', $intakeId)
            ->value('tuition_fee');

        return $this->formatFee($fee);
    }

    private function formatFee($fee): ?float {
        if ($fee === null) {
            return null;
        }

        $feeFloat = (float) $fee;
        if ($feeFloat <= 0) {
            return null;
        }

        return $feeFloat;
    }

    private function resolveIntakeId(Student $student): ?int {
        if (!empty($student->intake_id)) {
            return (int) $student->intake_id;
        }

        $query = Intake::query()
            ->where('status', Intake::STATUS_ACTIVE);

        // Not joining/filtering by program_id anymore as it's dropped from intakes

        $intakeMonth = (int) ($student->intake_month ?? 0);
        if ($intakeMonth >= 1 && $intakeMonth <= 12) {
            $query->whereMonth('start_date', $intakeMonth);
        }

        $intakeId = $query
            ->orderByDesc('start_date')
            ->value('id');

        if ($intakeId) {
            return (int) $intakeId;
        }

        // Fallback cuối: nếu không tìm được theo tháng, thử lấy intake active mới nhất theo org
        $fallbackQuery = Intake::query()
            ->where('status', Intake::STATUS_ACTIVE);

        $fallbackIntakeId = $fallbackQuery
            ->orderByDesc('start_date')
            ->value('id');

        return $fallbackIntakeId ? (int) $fallbackIntakeId : null;
    }
}

