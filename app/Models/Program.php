<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Program extends Model {
    protected $fillable = ['code', 'name', 'is_active', 'direct_commission_amount'];
}
