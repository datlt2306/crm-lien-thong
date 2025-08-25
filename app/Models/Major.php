<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Major extends Model {
    use HasFactory;
    protected $fillable = ['code', 'name', 'is_active'];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function organizations() {
        return $this->belongsToMany(Organization::class, 'major_organization');
    }
}
