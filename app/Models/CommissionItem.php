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

    // Các trạng thái commission
    public const STATUS_PENDING = 'pending'; // Chờ nhập học
    public const STATUS_PAYABLE = 'payable'; // Có thể thanh toán
    public const STATUS_PAID = 'paid'; // Đã thanh toán

    public static function getStatusOptions(): array {
        return [
            self::STATUS_PENDING => 'Chờ nhập học',
            self::STATUS_PAYABLE => 'Có thể thanh toán',
            self::STATUS_PAID => 'Đã thanh toán',
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
}
