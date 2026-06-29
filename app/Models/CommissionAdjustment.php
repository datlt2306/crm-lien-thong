<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditLog;

class CommissionAdjustment extends Model {
    use HasFactory, SoftDeletes, HasAuditLog;

    protected $fillable = [
        'commission_id',
        'recipient_collaborator_id',
        'amount',
        'reason',
        'status',
        'created_by',
        'payment_bill_path',
        'payment_confirmed_at',
        'payment_confirmed_by',
        'received_confirmed_at',
        'received_confirmed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'payment_confirmed_at' => 'datetime',
        'received_confirmed_at' => 'datetime',
    ];

    public function commission(): BelongsTo {
        return $this->belongsTo(Commission::class);
    }

    public function recipient(): BelongsTo {
        return $this->belongsTo(Collaborator::class, 'recipient_collaborator_id');
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function paymentConfirmedBy(): BelongsTo {
        return $this->belongsTo(User::class, 'payment_confirmed_by');
    }

    public function receivedConfirmedBy(): BelongsTo {
        return $this->belongsTo(User::class, 'received_confirmed_by');
    }
}
