<?php

namespace App\Observers;

use App\Models\Student;
use App\Services\CommissionService;
use App\Services\DashboardCacheService;

class StudentObserver {
    protected CommissionService $commissionService;

    public function __construct(CommissionService $commissionService) {
        $this->commissionService = $commissionService;
    }

    protected function bust(): void {
        DashboardCacheService::bumpVersion();
    }

    public function updated(Student $student): void {
        $this->bust();

        // Nếu sinh viên chuyển giao đoạn sang 'ENROLLED' (Đã nhập học)
        // Chúng ta sẽ mở khóa các khoản hoa hồng có trigger là 'student_enrolled'
        if ($student->isDirty('status') && $student->status === 'ENROLLED') {
            $this->commissionService->unlockCommissionsOnEnrollment($student);
        }
    }

    public function created(Student $student): void {
        $this->bust();
    }

    public function deleted(Student $student): void {
        $this->bust();
    }
}
