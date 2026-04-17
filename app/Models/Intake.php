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
     * Quan hệ: Có nhiều quotas (chỉ tiêu)
     */
    public function quotas() {
        return $this->hasMany(Quota::class);
    }

    public function programWindows() {
        return $this->hasMany(IntakeProgramWindow::class);
    }

    public static function computeOverallDatesFromWindows(array $windows): array {
        $startDates = [];
        $endDates = [];
        $deadlines = [];

        foreach ($windows as $w) {
            $start = $w['start_date'] ?? null;
            $end = $w['end_date'] ?? null;
            $deadline = $w['enrollment_deadline'] ?? null;

            if (!empty($start)) {
                $startDates[] = $start;
            }
            if (!empty($end)) {
                $endDates[] = $end;
            }
            if (!empty($deadline)) {
                $deadlines[] = $deadline;
            }
        }

        sort($startDates);
        rsort($endDates);
        rsort($deadlines);

        return [
            'start_date' => $startDates[0] ?? null,
            'end_date' => $endDates[0] ?? null,
            'enrollment_deadline' => $deadlines[0] ?? null,
        ];
    }

    /**
     * Quan hệ: Có nhiều students
     */
    public function students() {
        return $this->hasMany(Student::class);
    }



    /**
     * Scope: Lấy intakes đang active
     */
    public function scopeActive($query) {
        return $query->where('status', self::STATUS_ACTIVE);
    }


    /**
     * Kiểm tra xem đợt tuyển có còn nhận hồ sơ không
     */
    public function isAcceptingApplications(): bool {
        return $this->status === self::STATUS_ACTIVE &&
            $this->end_date >= now()->toDateString();
    }

    /**
     * Lấy tổng chỉ tiêu của đợt tuyển từ quotas đã gắn trực tiếp cho đợt.
     */
    public function getTotalTargetQuotaAttribute(): int {
        return (int) $this->quotas()
            ->where('status', Quota::STATUS_ACTIVE)
            ->sum('target_quota');
    }

    /**
     * Lấy tổng chỉ tiêu hiện tại (đã nhập học)
     */
    public function getTotalCurrentQuotaAttribute(): int {
        return (int) $this->quotas()
            ->where('status', Quota::STATUS_ACTIVE)
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
