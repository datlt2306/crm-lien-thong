<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

/**
 * Chỉ tiêu năm: (tổ chức, ngành, hệ, năm). Chia linh hoạt cho nhiều đợt trong năm.
 * Khi đợt 1 đủ target → hết; nếu chưa đủ → phần còn lại chuyển sang đợt sau.
 */
class AnnualQuota extends Model {
    use HasFactory;

    protected $table = 'annual_quotas';

    protected $fillable = [
        'name',
        'major_name',
        'program_name',
        'organization_id',
        'year',
        'target_quota',
        'current_quota',
        'status',
        'notes',
    ];

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    public const STATUS_FULL = 'full';

    public static function getStatusOptions(): array {
        return [
            self::STATUS_ACTIVE => 'Đang tuyển sinh',
            self::STATUS_INACTIVE => 'Tạm dừng',
            self::STATUS_FULL => 'Đã đủ chỉ tiêu',
        ];
    }

    public function organization() {
        return $this->belongsTo(Organization::class);
    }



    public function getAvailableSlotsAttribute(): int {
        return max(0, $this->target_quota - $this->current_quota);
    }

    public function hasAvailableSlots(): bool {
        return $this->available_slots > 0;
    }

    /**
     * Tăng current khi có người nhập học (payment verified / enrolled).
     */
    public function incrementCurrent(): void {
        $this->increment('current_quota');
        if ($this->current_quota >= $this->target_quota) {
            $this->update(['status' => self::STATUS_FULL]);
        }
    }

    /**
     * Giảm current khi học viên rút/hủy.
     */
    public function decrementCurrent(): void {
        $this->decrement('current_quota');
        if ($this->current_quota < $this->target_quota && $this->status === self::STATUS_FULL) {
            $this->update(['status' => self::STATUS_ACTIVE]);
        }
    }
}
