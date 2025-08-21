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
