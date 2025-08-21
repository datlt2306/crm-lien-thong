<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Major extends Model {
    protected $fillable = ['code', 'name', 'is_active'];

    public function organizations() {
        return $this->belongsToMany(Organization::class, 'major_organization');
    }
}
