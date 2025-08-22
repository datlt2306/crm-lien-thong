<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model {
    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'organization_id',
        'collaborator_id',
        'target_university',
        'major',
        'source',
        'status',
        'notes',
        'dob',
        'address',
    ];

    // Enum StudentStatus
    public const STATUS_NEW = 'new';
    public const STATUS_CONTACTED = 'contacted';
    public const STATUS_SUBMITTED = 'submitted';
    public const STATUS_APPROVED = 'approved';
    public const STATUS_ENROLLED = 'enrolled';
    public const STATUS_REJECTED = 'rejected';

    public static function getStatusOptions(): array {
        return [
            self::STATUS_NEW => 'New',
            self::STATUS_CONTACTED => 'Contacted',
            self::STATUS_SUBMITTED => 'Submitted',
            self::STATUS_APPROVED => 'Approved',
            self::STATUS_ENROLLED => 'Enrolled',
            self::STATUS_REJECTED => 'Rejected',
        ];
    }

    public function organization() {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function collaborator() {
        return $this->belongsTo(Collaborator::class, 'collaborator_id');
    }
}
