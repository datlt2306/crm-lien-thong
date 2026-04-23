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

        // 1. Notify the individual collaborator
        if ($student->collaborator_id && $student->collaborator) {
            $user = \App\Models\User::where('email', $student->collaborator->email)->first();
            if ($user) {
                try {
                    $user->notify(new \App\Notifications\StudentRegisteredNotification($student));
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Telegram Notification Error (Collaborator): ' . $e->getMessage());
                }
            }
        }

        // 2. Notify Super Admins
        $superAdmins = \App\Models\User::where('role', 'super_admin')->get();
        foreach ($superAdmins as $admin) {
            try {
                $admin->notify(new \App\Notifications\StudentRegisteredNotification($student));
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Telegram Notification Error (SuperAdmin): ' . $e->getMessage());
            }
        }
    }

    public function deleted(Student $student): void {
        $this->bust();
    }
}
