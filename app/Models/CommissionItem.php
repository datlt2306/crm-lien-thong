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
}
