<?php

namespace App\Services;

use App\Models\Intake;
use App\Models\Major;
use App\Models\Program;
use App\Models\Quota;
use App\Models\Student;

class StudentFeeService {
    /**
     * Tính "số tiền theo cấu hình" cho 1 sinh viên dựa trên:
     * - organization_id
     * - major (major_id hoặc tên major)
     * - intake (ưu tiên intake_id; fallback theo intake_month + program_type)
     *
     * Hiện tại cấu hình số tiền đang nằm ở `quotas.tuition_fee`.
     */
    public function getExpectedFeeForStudent(Student $student): ?float {
        if (!$student->organization_id) {
            return null;
        }

        $majorId = $this->resolveMajorId($student);
        if (!$majorId) {
            return null;
        }

        $intakeId = $this->resolveIntakeId($student);
        if (!$intakeId) {
            return null;
        }

        $fee = Quota::query()
            ->where('organization_id', $student->organization_id)
            ->where('major_id', $majorId)
            ->where('intake_id', $intakeId)
            ->value('tuition_fee');

        if ($fee === null) {
            return null;
        }

        $feeFloat = (float) $fee;
        if ($feeFloat <= 0) {
            return null;
        }

        return $feeFloat;
    }

    private function resolveMajorId(Student $student): ?int {
        if (!empty($student->major_id)) {
            return (int) $student->major_id;
        }

        $majorName = (string) ($student->major ?? '');
        if ($majorName === '') {
            return null;
        }

        $majorId = Major::query()
            ->where('name', $majorName)
            ->value('id');

        return $majorId ? (int) $majorId : null;
    }

    private function resolveIntakeId(Student $student): ?int {
        if (!empty($student->intake_id)) {
            return (int) $student->intake_id;
        }

        $query = Intake::query()
            ->where('organization_id', $student->organization_id)
            ->where('status', Intake::STATUS_ACTIVE);

        $programId = $this->resolveProgramIdFromProgramType($student);
        if ($programId) {
            $query->where('program_id', $programId);
        }

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

        // Fallback cuối: nếu không tìm được theo tháng, thử lấy intake active mới nhất theo org (+ program nếu có)
        $fallbackQuery = Intake::query()
            ->where('organization_id', $student->organization_id)
            ->where('status', Intake::STATUS_ACTIVE);

        if ($programId) {
            $fallbackQuery->where('program_id', $programId);
        }

        $fallbackIntakeId = $fallbackQuery
            ->orderByDesc('start_date')
            ->value('id');

        return $fallbackIntakeId ? (int) $fallbackIntakeId : null;
    }

    private function resolveProgramIdFromProgramType(Student $student): ?int {
        $programType = (string) ($student->program_type ?? '');
        if ($programType === '') {
            return null;
        }

        $programId = Program::query()
            ->where('code', $programType)
            ->value('id');

        return $programId ? (int) $programId : null;
    }
}

