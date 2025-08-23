<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Organization extends Model {
    use HasFactory;
    protected $fillable = [
        'name',
        'code',
        'status',
        'owner_id',
    ];

    /**
     * Quan hệ: Organization có nhiều CTV
     */
    public function ctvs() {
        return $this->hasMany(Collaborator::class);
    }

    /**
     * Quan hệ: Organization có nhiều Student
     */
    public function students() {
        return $this->hasMany(Student::class);
    }

    /**
     * Quan hệ: Chủ tổ chức (User)
     */
    public function owner() {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function majors() {
        return $this->belongsToMany(Major::class, 'major_organization')->withPivot(['quota', 'intake_months'])->withTimestamps();
    }

    public function programs() {
        return $this->belongsToMany(Program::class, 'organization_program')->withTimestamps();
    }
}
