<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class AuditLog extends Model
{
    use HasUlids;

    public $timestamps = false; // We only use created_at

    protected $fillable = [
        'event_group',
        'event_type',
        'auditable_type',
        'auditable_id',
        'user_id',
        'user_role',
        'student_id',
        'old_values',
        'new_values',
        'amount_diff',
        'reason',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        'metadata' => 'json',
        'amount_diff' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    // Event Groups
    public const GROUP_FINANCIAL = 'FINANCIAL';
    public const GROUP_SECURITY = 'SECURITY';
    public const GROUP_ACCOUNT_DELETION = 'ACCOUNT_DELETION';
    public const GROUP_SYSTEM = 'SYSTEM';

    // Event Types
    public const TYPE_CREATED = 'CREATED';
    public const TYPE_UPDATED = 'UPDATED';
    public const TYPE_DELETED = 'DELETED';
    public const TYPE_RESTORED = 'RESTORED';
    public const TYPE_REVERTED = 'REVERTED';

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Scope for Collaborator access: filter events related to their students
     */
    public function scopeForCollaborator($query, int $collaboratorId)
    {
        return $query->whereHas('student', function ($q) use ($collaboratorId) {
            $q->where('collaborator_id', $collaboratorId);
        });
    }
}
