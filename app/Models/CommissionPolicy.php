<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Database\Eloquent\SoftDeletes;

class CommissionPolicy extends Model {
    use SoftDeletes;
    protected $fillable = [
        'collaborator_id',
        'program_type',
        'role',
        'type',
        'amount_vnd',
        'payout_rules',
        'target_program_id',
        'percent',
        'trigger',
        'visibility',
        'priority',
        'active',
        'effective_from',
        'effective_to',
        'meta',
    ];

    protected $casts = [
        'amount_vnd' => 'decimal:2',
        'program_type' => 'array',
        'payout_rules' => 'array',
        'percent' => 'decimal:2',
        'priority' => 'integer',
        'active' => 'boolean',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'meta' => 'array',
    ];


    public function collaborator(): BelongsTo {
        return $this->belongsTo(Collaborator::class);
    }
}
