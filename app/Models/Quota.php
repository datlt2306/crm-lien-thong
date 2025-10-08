<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Quota extends Model {
    use HasFactory;

    protected $fillable = [
        'intake_id',
        'major_id',
        'program_id',
        'organization_id',
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
     * Quan hệ: Thuộc ngành học
     */
    public function major() {
        return $this->belongsTo(Major::class);
    }

    /**
     * Quan hệ: Thuộc chương trình đào tạo
     */
    public function program() {
        return $this->belongsTo(Program::class);
    }

    /**
     * Quan hệ: Thuộc tổ chức
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
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
     * Scope: Lấy quotas của organization
     */
    public function scopeForOrganization($query, $organizationId) {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Kiểm tra xem quota còn trống không
     */
    public function hasAvailableSlots(): bool {
        return $this->current_quota < $this->target_quota;
    }

    /**
     * Lấy số slot còn trống
     */
    public function getAvailableSlotsAttribute(): int {
        return max(0, $this->target_quota - $this->current_quota);
    }

    /**
     * Lấy tỷ lệ sử dụng quota (%)
     */
    public function getUtilizationPercentageAttribute(): float {
        if ($this->target_quota === 0) return 0;

        return round(($this->current_quota / $this->target_quota) * 100, 2);
    }

    /**
     * Cập nhật quota khi student nhập học
     */
    public function incrementCurrentQuota(): void {
        $this->increment('current_quota');

        // Tự động cập nhật status nếu đầy
        if ($this->current_quota >= $this->target_quota) {
            $this->update(['status' => self::STATUS_FULL]);
        }
    }

    /**
     * Giảm quota khi student rút hồ sơ
     */
    public function decrementCurrentQuota(): void {
        $this->decrement('current_quota');

        // Tự động cập nhật status về active nếu chưa đầy
        if ($this->current_quota < $this->target_quota && $this->status === self::STATUS_FULL) {
            $this->update(['status' => self::STATUS_ACTIVE]);
        }
    }
}
