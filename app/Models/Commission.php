<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commission extends Model {
    protected $fillable = [
        'payment_id',
        'student_id',
        'rule',
        'generated_at',
    ];

    protected $casts = [
        'rule' => 'array',
        'generated_at' => 'datetime',
    ];


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
