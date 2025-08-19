<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Collaborator extends Model {
    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'organization_id',
        'ref_id',
        'upline_id',
        'note',
        'status',
    ];

    /**
     * Quan hệ: Thuộc tổ chức
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Quan hệ: Upline (CTV cha)
     */
    public function upline() {
        return $this->belongsTo(self::class, 'upline_id');
    }

    /**
     * Quan hệ: Downlines (CTV con)
     */
    public function downlines() {
        return $this->hasMany(self::class, 'upline_id');
    }

    /**
     * Boot: Tự động sinh ref_id 8 ký tự in hoa, duy nhất khi tạo mới
     */
    protected static function boot() {
        parent::boot();
        static::creating(function ($model) {
            // ref_id
            if (empty($model->ref_id)) {
                do {
                    $ref = strtoupper(substr(bin2hex(random_bytes(8)), 0, 8));
                } while (self::where('ref_id', $ref)->exists());
                $model->ref_id = $ref;
            }
            // organization_id
            if (empty($model->organization_id)) {
                $user = auth()->user();
                if ($user && $user->role !== 'super_admin') {
                    $org = \App\Models\Organization::where('owner_id', $user->id)->first();
                    if ($org) {
                        $model->organization_id = $org->id;
                    }
                }
            }
        });
    }
}
