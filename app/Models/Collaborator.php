<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasAuditLog;

class Collaborator extends Model {
    use HasFactory, HasAuditLog;
    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'identity_card',
        'tax_code',
        'bank_name',
        'bank_account',
        'ref_id',
        'note',
        'status',
        'is_active',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'status' => 'string',
    ];


    // ===== LOẠI BỎ LOGIC CTV CẤP 2 =====
    // Đã xóa: upline(), downlines(), allDownlines(), downlineCommissionConfigs(), uplineCommissionConfigs()

    /**
     * Quan hệ: Commission items nhận được
     */
    public function commissionItems() {
        return $this->hasMany(CommissionItem::class, 'recipient_collaborator_id');
    }

    /**
     * Quan hệ: Payments
     */
    public function payments() {
        return $this->hasMany(Payment::class, 'primary_collaborator_id');
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
        });
    }
}
