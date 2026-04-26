<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\HasAuditLog;

use Illuminate\Notifications\Notifiable;

use Illuminate\Database\Eloquent\SoftDeletes;

class Collaborator extends Model {
    use HasFactory, HasAuditLog, Notifiable, SoftDeletes;

    /**
     * Kiểm tra xem CTV có muốn nhận loại thông báo này qua kênh này không
     */
    public function wantsNotification(string $type, string $channel): bool {
        // Tạm thời cho phép tất cả để test cho mượt
        return true;
    }

    /**
     * Route cho Telegram
     */
    public function routeNotificationForTelegram() {
        // Chat ID của Master Đạt (hoặc lấy từ ref_codes nếu là Proxy)
        return $this->telegram_chat_id;
    }
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
        'telegram_chat_id',
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

    /**
     * Quan hệ: Mã Ref phụ (Proxy)
     */
    public function refCodes() {
        return $this->hasMany(RefCode::class);
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
