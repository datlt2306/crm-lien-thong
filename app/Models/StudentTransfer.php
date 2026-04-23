<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentTransfer extends Model
{
    protected $fillable = [
        'student_id',
        'old_quota_id',
        'new_quota_id',
        'old_program_type',
        'new_program_type',
        'old_major',
        'new_major',
        'fee_difference',
        'reason',
        'created_by',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function oldQuota(): BelongsTo
    {
        return $this->belongsTo(Quota::class, 'old_quota_id');
    }

    public function newQuota(): BelongsTo
    {
        return $this->belongsTo(Quota::class, 'new_quota_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
