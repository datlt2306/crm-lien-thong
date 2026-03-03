<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CommissionPolicy extends Model {
    protected $fillable = [
        'organization_id',
        'collaborator_id',
        'program_type',
        'role',
        'type',
        'amount_vnd',
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
        'percent' => 'decimal:2',
        'priority' => 'integer',
        'active' => 'boolean',
        'effective_from' => 'datetime',
        'effective_to' => 'datetime',
        'meta' => 'array',
    ];

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }

    public function collaborator(): BelongsTo {
        return $this->belongsTo(Collaborator::class);
    }
}
