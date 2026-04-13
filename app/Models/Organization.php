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
        'organization_owner_id',
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
    public function organization_owner() {
        return $this->belongsTo(User::class, 'organization_owner_id');
    }

}
