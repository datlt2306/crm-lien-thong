<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Traits\HasAuditLog;

class PaymentAdjustment extends Model {
    use HasFactory, SoftDeletes, HasAuditLog;

    protected $fillable = [
        'payment_id',
        'student_id',
        'type',
        'amount',
        'reason',
        'refund_status',
        'refund_proof_path',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function payment(): BelongsTo {
        return $this->belongsTo(Payment::class);
    }

    public function student(): BelongsTo {
        return $this->belongsTo(Student::class);
    }

    public function creator(): BelongsTo {
        return $this->belongsTo(User::class, 'created_by');
    }
}
