<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DownlineCommissionConfig extends Model {
    protected $fillable = [
        'upline_collaborator_id',
        'downline_collaborator_id',
        'cq_amount',
        'vhvlv_amount',
        'payment_type',
        'is_active',
    ];

    protected $casts = [
        'cq_amount' => 'decimal:2',
        'vhvlv_amount' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    /**
     * Quan hệ: CTV cấp 1 (upline)
     */
    public function uplineCollaborator(): BelongsTo {
        return $this->belongsTo(Collaborator::class, 'upline_collaborator_id');
    }

    /**
     * Quan hệ: CTV cấp 2 (downline)
     */
    public function downlineCollaborator(): BelongsTo {
        return $this->belongsTo(Collaborator::class, 'downline_collaborator_id');
    }

    /**
     * Lấy số tiền theo loại chương trình
     */
    public function getAmountByProgramType(string $programType): float {
        return match ($programType) {
            'cq' => $this->cq_amount,
            'vhvlv' => $this->vhvlv_amount,
            default => 0,
        };
    }

    /**
     * Kiểm tra xem có phải trả ngay không
     */
    public function isImmediatePayment(): bool {
        return $this->payment_type === 'immediate';
    }

    /**
     * Kiểm tra xem có phải trả khi nhập học không
     */
    public function isOnEnrollmentPayment(): bool {
        return $this->payment_type === 'on_enrollment';
    }
}
