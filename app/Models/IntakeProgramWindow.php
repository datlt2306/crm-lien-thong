<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IntakeProgramWindow extends Model {
    use HasFactory;

    protected $fillable = [
        'intake_id',
        'program_name',
        'start_date',
        'end_date',
        'enrollment_deadline',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'enrollment_deadline' => 'date',
    ];

    public function intake() {
        return $this->belongsTo(Intake::class);
    }
}

