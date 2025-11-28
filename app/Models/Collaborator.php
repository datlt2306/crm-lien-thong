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
        // 'upline_id', // Đã loại bỏ - hệ thống chỉ còn 1 cấp
        'note',
        'status',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status' => 'string',
    ];

    /**
     * Quan hệ: Thuộc tổ chức
     */
    public function organization() {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    // ===== LOẠI BỎ LOGIC CTV CẤP 2 =====
    // Đã xóa: upline(), downlines(), allDownlines(), downlineCommissionConfigs(), uplineCommissionConfigs()

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
     * Quan hệ: Payments (khi là primary collaborator)
     */
    public function payments() {
        return $this->hasMany(Payment::class, 'primary_collaborator_id');
    }

    /**
     * Quan hệ: Payments (khi là sub collaborator)
     */
    public function subPayments() {
        return $this->hasMany(Payment::class, 'sub_collaborator_id');
    }

    // ===== LOẠI BỎ LOGIC CTV CẤP 2 =====
    // Đã xóa: isLevel1(), isLevel2()

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
                $user = \Illuminate\Support\Facades\Auth::user();
                if ($user && $user->role !== 'super_admin') {
                    $org = \App\Models\Organization::where('organization_owner_id', $user->id)->first();
                    if ($org) {
                        $model->organization_id = $org->id;
                    }
                }
            }
        });
    }
}
