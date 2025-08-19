<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Organization extends Model {
    protected $fillable = [
        'name',
        'code',
        'contact_name',
        'contact_phone',
        'status',
        'owner_id',
    ];

    /**
     * Quan hệ: Organization có nhiều CTV
     */
    public function ctvs() {
        return $this->hasMany(Ctv::class);
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
}
