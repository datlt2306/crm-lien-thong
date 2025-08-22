<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Payment extends Model {
    protected $fillable = [
        'organization_id',
        'student_id',
        'primary_collaborator_id',
        'sub_collaborator_id',
        'program_type',
        'amount',
        'bill_path',
        'status',
        'verified_by',
        'verified_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
    ];

    // Enum PaymentStatus - Quản lý tiền SV đã chuyển cho Org
    public const STATUS_NOT_PAID = 'not_paid';      // Chưa nộp tiền
    public const STATUS_SUBMITTED = 'submitted';    // Đã nộp (chờ xác minh)
    public const STATUS_VERIFIED = 'verified';      // Đã xác nhận

    public static function getStatusOptions(): array {
        return [
            self::STATUS_NOT_PAID => 'Chưa nộp tiền',
            self::STATUS_SUBMITTED => 'Đã nộp (chờ xác minh)',
            self::STATUS_VERIFIED => 'Đã xác nhận',
        ];
    }

    /**
     * Kiểm tra xem thanh toán có thể chuyển sang trạng thái tiếp theo không
     */
    public function canAdvanceToNextStatus(): bool {
        $nextStatuses = [
            self::STATUS_NOT_PAID => [self::STATUS_SUBMITTED],
            self::STATUS_SUBMITTED => [self::STATUS_VERIFIED],
            self::STATUS_VERIFIED => [],
        ];

        return !empty($nextStatuses[$this->status] ?? []);
    }

    /**
     * Lấy danh sách trạng thái tiếp theo có thể chuyển đến
     */
    public function getNextAvailableStatuses(): array {
        $nextStatuses = [
            self::STATUS_NOT_PAID => [self::STATUS_SUBMITTED],
            self::STATUS_SUBMITTED => [self::STATUS_VERIFIED],
            self::STATUS_VERIFIED => [],
        ];

        return $nextStatuses[$this->status] ?? [];
    }

    /**
     * Kiểm tra xem thanh toán đã hoàn thành chưa
     */
    public function isCompleted(): bool {
        return $this->status === self::STATUS_VERIFIED;
    }

    /**
     * Đánh dấu đã xác minh thanh toán
     */
    public function markAsVerified(int $verifiedBy): void {
        $this->update([
            'status' => self::STATUS_VERIFIED,
            'verified_by' => $verifiedBy,
            'verified_at' => now(),
        ]);
    }

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }

    public function student(): BelongsTo {
        return $this->belongsTo(Student::class);
    }

    public function primaryCollaborator(): BelongsTo {
        return $this->belongsTo(Collaborator::class, 'primary_collaborator_id');
    }

    public function subCollaborator(): BelongsTo {
        return $this->belongsTo(Collaborator::class, 'sub_collaborator_id');
    }

    public function verifier(): BelongsTo {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function commission(): HasOne {
        return $this->hasOne(Commission::class);
    }
}
