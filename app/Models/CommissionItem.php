<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionItem extends Model {
    protected $fillable = [
        'commission_id',
        'recipient_collaborator_id',
        'role',
        'amount',
        'status',
        'trigger',
        'payable_at',
        'paid_at',
        'visibility',
        'meta',
    ];

    // Các trạng thái commission - Quản lý việc trả hoa hồng cho CTV
    public const STATUS_PENDING = 'pending';       // Pending → đã sinh commission nhưng chưa đến hạn chi
    public const STATUS_PAYABLE = 'payable';       // Payable → đến hạn chi, CTV có thể nhận
    public const STATUS_PAID = 'paid';             // Paid → đã chi trả (ghi nhận bằng tay, đính bill)
    public const STATUS_CANCELLED = 'cancelled';   // Cancelled → huỷ (VD: SV không nhập học)

    public static function getStatusOptions(): array {
        return [
            self::STATUS_PENDING => 'Chờ nhập học',
            self::STATUS_PAYABLE => 'Có thể thanh toán',
            self::STATUS_PAID => 'Đã thanh toán',
            self::STATUS_CANCELLED => 'Đã huỷ',
        ];
    }

    protected $casts = [
        'amount' => 'decimal:2',
        'payable_at' => 'datetime',
        'paid_at' => 'datetime',
        'meta' => 'array',
    ];

    public function commission(): BelongsTo {
        return $this->belongsTo(Commission::class);
    }

    public function recipient(): BelongsTo {
        return $this->belongsTo(Collaborator::class, 'recipient_collaborator_id');
    }

    /**
     * Quan hệ: Wallet transaction liên quan
     */
    public function walletTransaction(): BelongsTo {
        return $this->belongsTo(WalletTransaction::class, 'commission_item_id');
    }

    /**
     * Kiểm tra xem có thể thanh toán không
     */
    public function canBePaid(): bool {
        return in_array($this->status, [self::STATUS_PAYABLE, self::STATUS_PENDING]);
    }

    /**
     * Đánh dấu đã thanh toán
     */
    public function markAsPaid(): void {
        $this->update([
            'status' => self::STATUS_PAID,
            'paid_at' => now(),
        ]);
    }

    /**
     * Đánh dấu có thể thanh toán
     */
    public function markAsPayable(): void {
        $this->update([
            'status' => self::STATUS_PAYABLE,
            'payable_at' => now(),
        ]);
    }

    /**
     * Đánh dấu đã huỷ
     */
    public function markAsCancelled(): void {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Kiểm tra xem commission đã hoàn thành chưa
     */
    public function isCompleted(): bool {
        return in_array($this->status, [self::STATUS_PAID, self::STATUS_CANCELLED]);
    }

    /**
     * Kiểm tra xem có thể chuyển sang trạng thái tiếp theo không
     */
    public function canAdvanceToNextStatus(): bool {
        $nextStatuses = [
            self::STATUS_PENDING => [self::STATUS_PAYABLE, self::STATUS_CANCELLED],
            self::STATUS_PAYABLE => [self::STATUS_PAID, self::STATUS_CANCELLED],
            self::STATUS_PAID => [],
            self::STATUS_CANCELLED => [],
        ];

        return !empty($nextStatuses[$this->status] ?? []);
    }

    /**
     * Lấy danh sách trạng thái tiếp theo có thể chuyển đến
     */
    public function getNextAvailableStatuses(): array {
        $nextStatuses = [
            self::STATUS_PENDING => [self::STATUS_PAYABLE, self::STATUS_CANCELLED],
            self::STATUS_PAYABLE => [self::STATUS_PAID, self::STATUS_CANCELLED],
            self::STATUS_PAID => [],
            self::STATUS_CANCELLED => [],
        ];

        return $nextStatuses[$this->status] ?? [];
    }
}
