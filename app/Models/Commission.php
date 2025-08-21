<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commission extends Model {
    protected $fillable = [
        'organization_id',
        'payment_id',
        'student_id',
        'rule',
        'generated_at',
    ];

    protected $casts = [
        'rule' => 'array',
        'generated_at' => 'datetime',
    ];

    public function organization(): BelongsTo {
        return $this->belongsTo(Organization::class);
    }

    public function payment(): BelongsTo {
        return $this->belongsTo(Payment::class);
    }

    public function student(): BelongsTo {
        return $this->belongsTo(Student::class);
    }

    public function items(): HasMany {
        return $this->hasMany(CommissionItem::class);
    }
}
