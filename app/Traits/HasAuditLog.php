<?php

namespace App\Traits;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait HasAuditLog
{
    public static function bootHasAuditLog(): void
    {
        static::created(function ($model) {
            $model->recordAuditLog(AuditLog::TYPE_CREATED);
        });

        static::updated(function ($model) {
            $model->recordAuditLog(AuditLog::TYPE_UPDATED);
        });

        static::deleted(function ($model) {
            $model->recordAuditLog(AuditLog::TYPE_DELETED);
        });

        if (method_exists(static::class, 'restored')) {
            static::restored(function ($model) {
                $model->recordAuditLog(AuditLog::TYPE_RESTORED);
            });
        }
    }

    public function recordAuditLog(string $eventType): void
    {
        $user = Auth::user();
        $changes = $this->getChanges();
        
        // If it's an update but nothing changed except timestamps, ignore
        if ($eventType === AuditLog::TYPE_UPDATED && empty(array_diff(array_keys($changes), ['updated_at']))) {
            return;
        }

        $eventGroup = $this->getAuditLogGroup($eventType);
        
        // Log important events: Financial, Account Deletion, System and Security
        // Only ignore if it's explicitly not in these groups (though it defaults to SYSTEM)
        if (!in_array($eventGroup, [
            AuditLog::GROUP_FINANCIAL, 
            AuditLog::GROUP_ACCOUNT_DELETION,
            AuditLog::GROUP_SYSTEM,
            AuditLog::GROUP_SECURITY
        ])) {
            return;
        }
        
        // Prepare data
        $oldValues = null;
        $newValues = null;
        $amountDiff = null;
        $metadata = null;
        $reason = Request::input('audit_reason') ?: ($this->edit_reason ?? null);

        if ($eventType === AuditLog::TYPE_UPDATED) {
            $oldValues = [];
            $newValues = [];
            foreach ($changes as $key => $newValue) {
                if ($key === 'updated_at') continue;
                $oldValues[$key] = $this->getOriginal($key);
                $newValues[$key] = $newValue;
            }

            // Calculate amount diff if applicable
            if (isset($newValues['amount']) || isset($newValues['fee'])) {
                $oldAmt = $this->getOriginal('amount') ?? $this->getOriginal('fee') ?? 0;
                $newAmt = $this->amount ?? $this->fee ?? 0;
                $amountDiff = (float)$newAmt - (float)$oldAmt;
            }
        } elseif ($eventType === AuditLog::TYPE_CREATED) {
            $newValues = $this->toArray();
        } elseif ($eventType === AuditLog::TYPE_DELETED) {
            $oldValues = $this->toArray();
            $metadata = ['snapshot' => $oldValues]; // Save snapshot for deletions
        }

        $metadata = $metadata ?: [];
        if ($batchId = Request::input('audit_batch_id')) {
            $metadata['batch_id'] = $batchId;
            $metadata['batch_count'] = Request::input('audit_batch_count');
        }

        AuditLog::create([
            'event_group' => $eventGroup,
            'event_type' => $eventType,
            'auditable_type' => get_class($this),
            'auditable_id' => $this->id,
            'user_id' => Auth::id(),
            'user_role' => $user?->role ?? ($user?->roles?->first()?->name ?? 'system'),
            'student_id' => $this->getAuditLogStudentId($eventType),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'amount_diff' => $amountDiff,
            'reason' => $reason,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'metadata' => $metadata,
        ]);
    }

    protected function getAuditLogGroup(string $eventType): string
    {
        $class = get_class($this);

        if ($eventType === AuditLog::TYPE_DELETED && in_array($class, [\App\Models\User::class, \App\Models\Collaborator::class, \App\Models\Student::class])) {
            return AuditLog::GROUP_ACCOUNT_DELETION;
        }

        if (in_array($class, [\App\Models\Payment::class, \App\Models\CommissionItem::class])) {
            return AuditLog::GROUP_FINANCIAL;
        }

        // Student updates can be financial (fee changes) or system
        if ($class === \App\Models\Student::class && $eventType === AuditLog::TYPE_UPDATED) {
            $changes = $this->getChanges();
            if (isset($changes['fee'])) {
                return AuditLog::GROUP_FINANCIAL;
            }
        }

        return AuditLog::GROUP_SYSTEM;
    }

    protected function getAuditLogStudentId(?string $eventType = null): ?int
    {
        $class = get_class($this);

        // Nếu là xóa vĩnh viễn chính học viên này, không trả về student_id 
        // để tránh lỗi khóa ngoại trong audit_logs khi bản ghi student đã biến mất
        if ($class === \App\Models\Student::class && $eventType === AuditLog::TYPE_DELETED) {
            return null;
        }

        if (isset($this->student_id)) {
            return $this->student_id;
        }
        
        if ($class === \App\Models\Student::class) {
            return $this->id;
        }

        // Special case: CommissionItem gets student_id from parent Commission
        if ($class === \App\Models\CommissionItem::class && $this->commission) {
            return $this->commission->student_id;
        }

        return null;
    }
}
