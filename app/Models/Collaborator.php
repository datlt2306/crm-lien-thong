<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Collaborator extends Model {
    use HasFactory;
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

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Quan hệ: Thuộc tổ chức
     */
    public function organization() {
        return $this->belongsTo(Organization::class, 'organization_id');
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
     * Quan hệ: Tất cả downlines (bao gồm cả cấp 2, 3...)
     */
    public function allDownlines() {
        return $this->hasMany(self::class, 'upline_id')->with('allDownlines');
    }

    /**
     * Quan hệ: Cấu hình hoa hồng tuyến dưới (khi là upline)
     */
    public function downlineCommissionConfigs() {
        return $this->hasMany(DownlineCommissionConfig::class, 'upline_collaborator_id');
    }

    /**
     * Quan hệ: Cấu hình hoa hồng tuyến dưới (khi là downline)
     */
    public function uplineCommissionConfigs() {
        return $this->hasMany(DownlineCommissionConfig::class, 'downline_collaborator_id');
    }

    /**
     * Quan hệ: Wallet
     */
    public function wallet() {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Quan hệ: Commission items nhận được
     */
    public function commissionItems() {
        return $this->hasMany(CommissionItem::class, 'recipient_collaborator_id');
    }

    /**
     * Kiểm tra xem có phải CTV cấp 1 không (có upline là null)
     */
    public function isLevel1() {
        return is_null($this->upline_id);
    }

    /**
     * Kiểm tra xem có phải CTV cấp 2 không (có upline là CTV cấp 1)
     */
    public function isLevel2() {
        return !is_null($this->upline_id) && $this->upline && $this->upline->isLevel1();
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
