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

    public function uplineCollaborator(): BelongsTo {
        return $this->belongsTo(Collaborator::class, 'upline_collaborator_id');
    }

    public function downlineCollaborator(): BelongsTo {
        return $this->belongsTo(Collaborator::class, 'downline_collaborator_id');
    }

    /**
     * Lấy số tiền theo loại chương trình
     */
    public function getAmountByProgramType(string $programType): float {
        return match (strtolower($programType)) {
            'cq', 'regular' => (float) $this->cq_amount,
            'vhvlv', 'part_time' => (float) $this->vhvlv_amount,
            default => 0,
        };
    }

    /**
     * Kiểm tra xem có phải thanh toán ngay không
     */
    public function isImmediatePayment(): bool {
        return $this->payment_type === 'immediate';
    }
}
