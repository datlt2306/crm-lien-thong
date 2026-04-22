<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Traits\HasAuditLog;

class Payment extends Model {
    use HasFactory, HasAuditLog;
    protected $fillable = [
        'student_id',
        'primary_collaborator_id',
        'program_type',
        'amount',
        'bill_path',
        'receipt_path',
        'receipt_number',
        'status',
        'verified_by',
        'verified_at',
        'edit_reason',
        'edited_at',
        'edited_by',
        'receipt_uploaded_by',
        'receipt_uploaded_at',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'verified_at' => 'datetime',
        'edited_at' => 'datetime',
        'receipt_uploaded_at' => 'datetime',
    ];

    // Enum PaymentStatus - Quản lý tiền SV đã chuyển cho Org
    public const STATUS_NOT_PAID = 'not_paid';      // Chưa nộp tiền
    public const STATUS_SUBMITTED = 'submitted';    // Đã nộp (chờ xác minh)
    public const STATUS_VERIFIED = 'verified';      // Đã nộp tiền (đã xác minh)
    public const STATUS_REVERTED = 'reverted';      // Đã hoàn trả

    public static function getStatusOptions(): array {
        return [
            self::STATUS_NOT_PAID => 'Chưa nộp tiền',
            self::STATUS_SUBMITTED => 'Đã nộp (chờ xác minh)',
            self::STATUS_VERIFIED => 'Đã xác nhận',
            self::STATUS_REVERTED => 'Đã hoàn trả',
        ];
    }

    /**
     * Kiểm tra xem thanh toán có thể chuyển sang trạng thái tiếp theo không
     */
    public function canAdvanceToNextStatus(): bool {
        $nextStatuses = [
            self::STATUS_NOT_PAID => [self::STATUS_SUBMITTED],
            self::STATUS_SUBMITTED => [self::STATUS_VERIFIED],
            self::STATUS_VERIFIED => [self::STATUS_REVERTED],
            self::STATUS_REVERTED => [self::STATUS_VERIFIED],
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
            self::STATUS_VERIFIED => [self::STATUS_REVERTED], // Có thể hoàn trả
            self::STATUS_REVERTED => [self::STATUS_VERIFIED], // Có thể xác nhận lại
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


    public function generateStandardBillPath(string $extension): string {
        $student = $this->student;
        $year = now()->format('Y');
        
        $profileCode = $student->profile_code;
        $fullName = $student->full_name;
        $major = $student->major;
        
        $systemCode = match (strtoupper((string)$this->program_type)) {
            'REGULAR' => 'CQ',
            'PART_TIME' => 'VHVL',
            'DISTANCE' => 'TX',
            default => $this->program_type
        };

        // Format: Hóa đơn đăng ký/2026/HS2026000194_Dat Le Trong_CNTT_CQ.png
        $fileName = "{$profileCode}_{$fullName}_{$major}_{$systemCode}.{$extension}";
        
        return "Hóa đơn đăng ký/{$year}/{$fileName}";
    }
    public function generateStandardReceiptPath(string $extension): string {
        $student = $this->student;
        $year = now()->format('Y');
        
        $profileCode = $student->profile_code;
        $fullName = $student->full_name;
        $major = $student->major;
        
        $systemCode = match (strtoupper((string)$this->program_type)) {
            'REGULAR' => 'CQ',
            'PART_TIME' => 'VHVL',
            'DISTANCE' => 'TX',
            default => $this->program_type
        };

        // Format: Phiếu thu/2026/HS2026000194_Dat Le Trong_CNTT_CQ.png
        $fileName = "{$profileCode}_{$fullName}_{$major}_{$systemCode}.{$extension}";
        
        return "Phiếu thu/{$year}/{$fileName}";
    }

    public function getReceiptUrlAttribute(): ?string {
        if (!$this->receipt_path) return null;
        return route('files.receipt.view', $this->id);
    }

    public function getBillUrlAttribute(): ?string {
        if (!$this->bill_path) return null;
        return route('files.bill.view', $this->id);
    }

    public function student(): BelongsTo {
        return $this->belongsTo(Student::class);
    }

    public function primaryCollaborator(): BelongsTo {
        return $this->belongsTo(Collaborator::class, 'primary_collaborator_id');
    }

    public function editedBy(): BelongsTo {
        return $this->belongsTo(User::class, 'edited_by');
    }

    public function verifier(): BelongsTo {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function commission(): HasOne {
        return $this->hasOne(Commission::class);
    }

    /**
     * Kiểm tra xem đã có bất kỳ mục hoa hồng nào được xác nhận chi hoặc đã chi chưa
     */
    public function hasConfirmedCommission(): bool {
        if (!$this->commission) {
            return false;
        }

        return $this->commission->items()
            ->whereIn('status', [
                CommissionItem::STATUS_PAID,
                CommissionItem::STATUS_PAYMENT_CONFIRMED,
                CommissionItem::STATUS_RECEIVED_CONFIRMED
            ])
            ->exists();
    }
}
