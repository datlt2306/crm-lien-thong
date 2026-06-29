<?php

namespace App\Observers;

use App\Models\Student;
use App\Services\CommissionService;
use App\Services\DashboardCacheService;

class StudentObserver {
    protected CommissionService $commissionService;
    protected \App\Services\QuotaService $quotaService;

    public function __construct(CommissionService $commissionService, \App\Services\QuotaService $quotaService) {
        $this->commissionService = $commissionService;
        $this->quotaService = $quotaService;
    }

    protected function bust(): void {
        DashboardCacheService::bumpVersion();
    }

    public function updated(Student $student): void {
        $this->bust();

        // Nếu sinh viên chuyển giao đoạn sang 'enrolled' (Đã nhập học)
        if ($student->isDirty('status') && $student->status === 'enrolled') {
            $this->commissionService->unlockCommissionsOnEnrollment($student);
        }

        // Xử lý chuyển ngành / chuyển đợt (chuyển quota)
        if ($student->isDirty('quota_id')) {
            $oldQuotaId = $student->getOriginal('quota_id');
            $newQuotaId = $student->quota_id;
            $this->quotaService->handleStudentTransfer($student, $oldQuotaId, $newQuotaId);
        }

        // Xử lý hủy / từ chối hồ sơ (giải phóng quota)
        if ($student->isDirty('status') && in_array($student->status, [Student::STATUS_REJECTED, Student::STATUS_DROPPED])) {
            $this->quotaService->handleStudentCancellation($student);
        }
        
        // Ngược lại, nếu hồ sơ được phục hồi từ trạng thái hủy
        if ($student->isDirty('status') && in_array($student->getOriginal('status'), [Student::STATUS_REJECTED, Student::STATUS_DROPPED]) && !in_array($student->status, [Student::STATUS_REJECTED, Student::STATUS_DROPPED])) {
            $this->quotaService->handleStudentRestoration($student);
        }
    }

    public function saving(Student $student): void {
        // Nếu học viên "đến trực tiếp" (walkin) thì không gán CTV
        if (($student->source ?? null) === 'walkin') {
            $student->collaborator_id = null;
        }

        // Tối ưu N+1: Sử dụng static cache để tránh truy vấn liên tục khi tạo/sửa hàng loạt
        static $intakeCache = [];
        static $quotaCache = [];

        // Lấy thông tin đợt tuyển và chỉ tiêu để lưu cache vào bản ghi
        if ($student->isDirty('intake_id')) {
            if ($student->intake_id) {
                if (!isset($intakeCache[$student->intake_id])) {
                    $intakeCache[$student->intake_id] = $student->intake()->find($student->intake_id);
                }
                $intake = $intakeCache[$student->intake_id];
                $student->intake_month = $intake?->start_date?->format('n');
            } else {
                $student->intake_month = null;
            }
        }

        if ($student->isDirty('quota_id') && $student->quota_id) {
            if (!isset($quotaCache[$student->quota_id])) {
                $quotaCache[$student->quota_id] = \App\Models\Quota::find($student->quota_id);
            }
            $quota = $quotaCache[$student->quota_id];
            if ($quota) {
                $student->major = $quota->major_name ?? $quota->name;
                $student->program_type = $quota->program_name;
            }
        }
    }

    public function creating(Student $student): void {
        if (!empty($student->profile_code)) {
            return;
        }

        // Tạo profile_code tạm thời nếu chưa có ID (vì creating chưa có ID)
        // Chúng ta sẽ cập nhật chính xác trong created()
        $year = now()->format('Y');
        $randomPart = strtoupper(\Illuminate\Support\Str::random(4));
        $student->profile_code = "HS{$year}{$randomPart}TMP";
    }

    public function created(Student $student): void {
        $this->bust();

        // Cập nhật Profile Code chính thức dựa trên ID vừa tạo
        if (str_ends_with($student->profile_code, 'TMP')) {
            $year = $student->created_at?->format('Y') ?? now()->format('Y');
            $randomPart = strtoupper(\Illuminate\Support\Str::random(4));
            $idPart = sprintf('%03d', $student->id % 1000);
            $student->profile_code = "HS{$year}{$randomPart}{$idPart}";
            $student->saveQuietly();
        }

        $sentTelegramChatIds = [];

        // 1. Notify the Proxy CTV (if any)
        if ($student->source_ref) {
            $proxy = \App\Models\RefCode::where('code', $student->source_ref)->first();
            if ($proxy && $proxy->telegram_chat_id) {
                try {
                    $proxy->notify(new \App\Notifications\StudentRegisteredNotification($student));
                    $sentTelegramChatIds[] = $proxy->telegram_chat_id;
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Telegram Notification Error (Proxy): ' . $e->getMessage());
                }
            }
        }

        // 2. Notify the Master CTV
        if ($student->collaborator_id && $student->collaborator) {
            $user = \App\Models\User::where('email', $student->collaborator->email)->first();
            if ($user && $user->telegram_chat_id && !in_array($user->telegram_chat_id, $sentTelegramChatIds)) {
                try {
                    // forceToNotifiable = true ensures the Master always receives the notification
                    $user->notify(new \App\Notifications\StudentRegisteredNotification($student, true));
                    $sentTelegramChatIds[] = $user->telegram_chat_id;
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error('Telegram Notification Error (Master): ' . $e->getMessage());
                }
            }
        }

        // 3. Notify Super Admins
        $superAdmins = \App\Models\User::where('role', 'super_admin')->get();
        foreach ($superAdmins as $admin) {
            if ($admin->telegram_chat_id && in_array($admin->telegram_chat_id, $sentTelegramChatIds)) {
                continue;
            }
            try {
                $admin->notify(new \App\Notifications\StudentRegisteredNotification($student));
                if ($admin->telegram_chat_id) {
                    $sentTelegramChatIds[] = $admin->telegram_chat_id;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Telegram Notification Error (SuperAdmin): ' . $e->getMessage());
            }
        }
    }

    public function deleted(Student $student): void {
        $this->bust();
        
        // Chỉ xử lý hoàn trả chỉ tiêu nếu trước đó hồ sơ vẫn đang chiếm chỗ (không phải bị từ chối/bỏ học trước đó)
        if (!in_array($student->status, [Student::STATUS_REJECTED, Student::STATUS_DROPPED])) {
            $this->quotaService->handleStudentCancellation($student);
        }
    }
}
