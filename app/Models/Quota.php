<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Illuminate\Database\Eloquent\SoftDeletes;

class Quota extends Model {
    use HasFactory, SoftDeletes;

    protected $appends = [
        'available_slots',
    ];

    protected $fillable = [
        'name',
        'major_name',
        'program_name',
        'intake_id',
        'target_quota',
        'current_quota',
        'pending_quota',
        'reserved_quota',
        'tuition_fee',
        'notes',
        'status',
    ];

    protected $casts = [
        'tuition_fee' => 'decimal:2',
    ];

    // Constants for status
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_FULL = 'full';

    public static function getStatusOptions(): array {
        return [
            self::STATUS_ACTIVE => 'Đang tuyển sinh',
            self::STATUS_INACTIVE => 'Tạm dừng',
            self::STATUS_FULL => 'Đã đầy',
        ];
    }

    /**
     * Quan hệ: Thuộc đợt tuyển sinh
     */
    public function intake() {
        return $this->belongsTo(Intake::class);
    }




    /**
     * Scope: Lấy quotas đang active
     */
    public function scopeActive($query) {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Lấy quotas của intake
     */
    public function scopeForIntake($query, $intakeId) {
        return $query->where('intake_id', $intakeId);
    }


    /**
     * Kiểm tra xem quota còn trống không
     */
    public function hasAvailableSlots(): bool {
        return $this->current_quota < $this->target_quota;
    }

    /**
     * Lấy số slot còn trống (Trừ cả người đã nhập học và người đang chờ)
     */
    public function getAvailableSlotsAttribute(): int {
        return max(0, $this->target_quota - ($this->current_quota + $this->pending_quota));
    }

    /**
     * Lấy tỷ lệ sử dụng quota (%)
     */
    public function getUtilizationPercentageAttribute(): float {
        if ($this->target_quota === 0) return 0;

        return round((($this->current_quota + $this->pending_quota) / $this->target_quota) * 100, 2);
    }

    /**
     * Cập nhật quota khi student nhập học (Chuyển từ pending sang current)
     */
    public function incrementCurrentQuota(): void {
        $this->increment('current_quota');
        
        // Nếu trước đó có ở pending thì trừ đi (phòng hờ logic service gọi trực tiếp)
        if ($this->pending_quota > 0) {
            $this->decrement('pending_quota');
        }

        // Tự động cập nhật status nếu đầy
        if (($this->current_quota + $this->pending_quota) >= $this->target_quota) {
            $this->update(['status' => self::STATUS_FULL]);
        }
    }

    /**
     * Tăng pending quota khi có registration mới
     */
    public function incrementPendingQuota(): void {
        $this->increment('pending_quota');

        if (($this->current_quota + $this->pending_quota) >= $this->target_quota) {
            $this->update(['status' => self::STATUS_FULL]);
        }
    }

    /**
     * Giảm pending quota
     */
    public function decrementPendingQuota(): void {
        if ($this->pending_quota > 0) {
            $this->decrement('pending_quota');
        }

        if (($this->current_quota + $this->pending_quota) < $this->target_quota && $this->status === self::STATUS_FULL) {
            $this->update(['status' => self::STATUS_ACTIVE]);
        }
    }

    /**
     * Giảm quota khi student rút hồ sơ
     */
    public function decrementCurrentQuota(): void {
        if ($this->current_quota > 0) {
            $this->decrement('current_quota');
        }

        // Tự động cập nhật status về active nếu chưa đầy
        if (($this->current_quota + $this->pending_quota) < $this->target_quota && $this->status === self::STATUS_FULL) {
            $this->update(['status' => self::STATUS_ACTIVE]);
        }
    }
}
