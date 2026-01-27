<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Intake extends Model {
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'start_date',
        'end_date',
        'enrollment_deadline',
        'status',
        'organization_id',
        'program_id',
        'settings',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'enrollment_deadline' => 'date',
        'settings' => 'array',
    ];

    // Constants for status
    public const STATUS_UPCOMING = 'upcoming';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_CLOSED = 'closed';
    public const STATUS_CANCELLED = 'cancelled';

    public static function getStatusOptions(): array {
        return [
            self::STATUS_UPCOMING => 'Sắp mở',
            self::STATUS_ACTIVE => 'Đang tuyển sinh',
            self::STATUS_CLOSED => 'Đã đóng',
            self::STATUS_CANCELLED => 'Đã hủy',
        ];
    }

    /**
     * Quan hệ: Thuộc tổ chức
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Quan hệ: Thuộc chương trình đào tạo
     */
    public function program() {
        return $this->belongsTo(Program::class);
    }

    /**
     * Quan hệ: Có nhiều quotas (chỉ tiêu)
     */
    public function quotas() {
        return $this->hasMany(Quota::class);
    }

    /**
     * Quan hệ: Có nhiều students
     */
    public function students() {
        return $this->hasMany(Student::class);
    }

    /**
     * Các chỉ tiêu năm được áp dụng cho đợt này
     */
    public function annualQuotas() {
        return $this->belongsToMany(AnnualQuota::class, 'annual_quota_intake')
            ->withTimestamps();
    }

    /**
     * Scope: Lấy intakes đang active
     */
    public function scopeActive($query) {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope: Lấy intakes của organization
     */
    public function scopeForOrganization($query, $organizationId) {
        return $query->where('organization_id', $organizationId);
    }

    /**
     * Kiểm tra xem đợt tuyển có còn nhận hồ sơ không
     */
    public function isAcceptingApplications(): bool {
        return $this->status === self::STATUS_ACTIVE &&
            $this->end_date >= now()->toDateString();
    }

    /**
     * Lấy tổng chỉ tiêu của đợt tuyển (ưu tiên từ relationship, fallback: annual_quotas theo năm)
     */
    public function getTotalTargetQuotaAttribute(): int {
        // Ưu tiên: Lấy từ relationship (đã liên kết cụ thể)
        $linkedQuotas = $this->annualQuotas()
            ->where('status', AnnualQuota::STATUS_ACTIVE)
            ->get();

        if ($linkedQuotas->isNotEmpty()) {
            return (int) $linkedQuotas->sum('target_quota');
        }

        // Fallback: Lấy tất cả annual_quotas của năm đó
        $year = $this->start_date?->format('Y') ?? now()->format('Y');
        return (int) AnnualQuota::where('organization_id', $this->organization_id)
            ->where('year', $year)
            ->where('status', AnnualQuota::STATUS_ACTIVE)
            ->sum('target_quota');
    }

    /**
     * Lấy tổng chỉ tiêu hiện tại (đã nhập học)
     */
    public function getTotalCurrentQuotaAttribute(): int {
        // Ưu tiên: Lấy từ relationship (đã liên kết cụ thể)
        $linkedQuotas = $this->annualQuotas()
            ->where('status', AnnualQuota::STATUS_ACTIVE)
            ->get();

        if ($linkedQuotas->isNotEmpty()) {
            return (int) $linkedQuotas->sum('current_quota');
        }

        // Fallback: Lấy tất cả annual_quotas của năm đó
        $year = $this->start_date?->format('Y') ?? now()->format('Y');
        return (int) AnnualQuota::where('organization_id', $this->organization_id)
            ->where('year', $year)
            ->where('status', AnnualQuota::STATUS_ACTIVE)
            ->sum('current_quota');
    }

    /**
     * Lấy tỷ lệ sử dụng chỉ tiêu (%)
     */
    public function getQuotaUtilizationAttribute(): float {
        $target = $this->total_target_quota;
        if ($target === 0) return 0;

        return round(($this->total_current_quota / $target) * 100, 2);
    }
}
