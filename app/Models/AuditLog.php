<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class AuditLog extends Model
{
    use HasUlids;

    public $timestamps = false; // We only use created_at

    protected $fillable = [
        'event_group',
        'event_type',
        'auditable_type',
        'auditable_id',
        'user_id',
        'user_role',
        'student_id',
        'old_values',
        'new_values',
        'amount_diff',
        'reason',
        'ip_address',
        'user_agent',
        'metadata',
        'created_at',
    ];

    protected $casts = [
        'old_values' => 'json',
        'new_values' => 'json',
        'metadata' => 'json',
        'amount_diff' => 'decimal:2',
        'created_at' => 'datetime',
    ];

    // Event Groups
    public const GROUP_FINANCIAL = 'FINANCIAL';
    public const GROUP_SECURITY = 'SECURITY';
    public const GROUP_ACCOUNT_DELETION = 'ACCOUNT_DELETION';
    public const GROUP_SYSTEM = 'SYSTEM';

    // Event Types
    public const TYPE_CREATED = 'CREATED';
    public const TYPE_UPDATED = 'UPDATED';
    public const TYPE_DELETED = 'DELETED';
    public const TYPE_RESTORED = 'RESTORED';
    public const TYPE_BATCH_CONFIRMED = 'BATCH_CONFIRMED';
    public const TYPE_REVERTED = 'REVERTED';

    /**
     * Tên hành động ngắn gọn (cho danh sách Table)
     */
    public function getFriendlyActionName(): string
    {
        $modelName = str_replace('App\\Models\\', '', $this->auditable_type);
        $eventType = strtoupper($this->event_type);
        $new = $this->new_values ?? [];

        if ($eventType === 'UPDATED') {
            if ($modelName === 'CommissionItem' && isset($new['status'])) {
                return match ($new['status']) {
                    'payment_confirmed' => 'Chốt sổ & Chi tiền',
                    'payable' => 'Hoàn tác chốt sổ',
                    default => 'Cập nhật hoa hồng',
                };
            }
            if ($modelName === 'Payment' && isset($new['status'])) {
                return match ($new['status']) {
                    'verified' => 'Xác nhận thu tiền',
                    'refunded' => 'Hoàn trả tiền',
                    default => 'Cập nhật phiếu thu',
                };
            }
            if ($modelName === 'Student' && isset($new['status'])) {
                return 'Đổi trạng thái học viên';
            }
            if ($modelName === 'Student' && isset($new['fee'])) {
                return 'Điều chỉnh học phí';
            }
            return 'Cập nhật dữ liệu';
        }

        if ($eventType === 'CREATED') {
            return match ($modelName) {
                'Student' => 'Đăng ký học viên',
                'Payment' => 'Lập phiếu thu',
                'CommissionItem' => 'Phát sinh hoa hồng',
                default => 'Tạo mới dữ liệu',
            };
        }

        return match ($eventType) {
            'DELETED' => 'Xóa dữ liệu',
            'RESTORED' => 'Khôi phục',
            default => $this->event_type,
        };
    }

    /**
     * Mô tả hành động chi tiết (cho trang Xem chi tiết)
     */
    public function getDetailedActionName(): string
    {
        $modelName = str_replace('App\\Models\\', '', $this->auditable_type);
        $eventType = strtoupper($this->event_type);
        $new = $this->new_values ?? [];
        $studentName = $this->student?->full_name ?? 'N/A';

        if ($eventType === 'UPDATED') {
            if ($modelName === 'CommissionItem' && isset($new['status'])) {
                $item = $this->auditable;
                $amount = number_format($item?->amount ?? ($new['amount'] ?? $this->old_values['amount'] ?? 0), 0, ',', '.') . ' VNĐ';
                
                // Thử lấy tên CTV từ nhiều nguồn (đề phòng N+1 hoặc đã xóa)
                $collaboratorName = $item?->recipient?->full_name 
                    ?? $item?->commission?->collaborator?->full_name 
                    ?? 'N/A';

                return match ($new['status']) {
                    'payment_confirmed' => "Chi $amount hoa hồng cho CTV $collaboratorName (Học viên: $studentName)",
                    'payable' => "Hoàn tác chốt sổ (Học viên: $studentName)",
                    default => "Cập nhật trạng thái hoa hồng của $studentName",
                };
            }
            
            if ($modelName === 'Payment' && isset($new['status'])) {
                $amount = number_format($this->auditable?->amount ?? 0, 0, ',', '.') . ' VNĐ';
                return match ($new['status']) {
                    'verified' => "Xác nhận thu $amount từ học viên $studentName",
                    'refunded' => "Hoàn trả $amount cho học viên $studentName",
                    'cancelled' => "Hủy phiếu thu học viên $studentName",
                    default => "Cập nhật phiếu thu của $studentName",
                };
            }

            if ($modelName === 'Student' && isset($new['status'])) {
                return "Chuyển học viên $studentName sang trạng thái mới";
            }
            
            if ($modelName === 'Student' && isset($new['fee'])) {
                $oldFee = number_format($this->old_values['fee'] ?? 0, 0, ',', '.') . ' VNĐ';
                $newFee = number_format($new['fee'] ?? 0, 0, ',', '.') . ' VNĐ';
                return "Điều chỉnh học phí của $studentName (từ $oldFee thành $newFee)";
            }

            return "Cập nhật thông tin " . $this->getFriendlyModelName();
        }

        if ($eventType === 'CREATED') {
            return match ($modelName) {
                'Student' => "Đăng ký học viên mới: $studentName",
                'Payment' => "Lập phiếu thu mới cho học viên $studentName",
                'CommissionItem' => "Phát sinh hoa hồng cho $studentName",
                default => 'Tạo mới ' . $this->getFriendlyModelName(),
            };
        }

        return $this->getFriendlyActionName();
    }

    /**
     * Tạo đoạn tóm tắt dạng văn bản thuần, dành cho Kế toán
     * Kế toán đọc đoạn này là hiểu ngay chuyện gì xảy ra, không cần nhìn bảng kỹ thuật.
     */
    public function getHumanSummary(): string
    {
        $modelName = str_replace('App\\Models\\', '', $this->auditable_type);
        $eventType = strtoupper($this->event_type);
        $new = $this->new_values ?? [];
        $old = $this->old_values ?? [];
        $studentName = $this->student?->full_name ?? 'N/A';
        $userName = $this->user?->name ?? 'Hệ thống';
        $time = $this->created_at?->format('H:i ngày d/m/Y') ?? '';
        $reason = $this->reason;

        $lines = [];
        $lines[] = "🕐 Thời gian: $time";
        $lines[] = "👤 Người thực hiện: $userName";

        if ($eventType === 'UPDATED' && $modelName === 'CommissionItem' && isset($new['status'])) {
            $item = $this->auditable;
            $amount = number_format($item?->amount ?? ($new['amount'] ?? $old['amount'] ?? 0), 0, ',', '.') . ' VNĐ';
            $collaboratorName = $item?->recipient?->full_name
                ?? $item?->commission?->collaborator?->full_name
                ?? 'N/A';

            if ($new['status'] === 'payment_confirmed') {
                $oldStatusLabel = self::translateStatus($old['status'] ?? '');
                $lines[] = "📋 Nội dung: Chốt sổ và chi hoa hồng";
                $lines[] = "👨‍🎓 Học viên: $studentName";
                $lines[] = "🤝 CTV nhận tiền: $collaboratorName";
                $lines[] = "💰 Số tiền chi: $amount";
                $lines[] = "🔄 Trạng thái: $oldStatusLabel → Đã chốt & Đã chi";
                if (isset($new['payment_confirmed_at'])) {
                    $lines[] = "📅 Ngày xác nhận chi: " . date('d/m/Y H:i', strtotime($new['payment_confirmed_at']));
                }
            } elseif ($new['status'] === 'payable') {
                $lines[] = "📋 Nội dung: Hoàn tác chốt sổ (đưa về trạng thái Có thể thanh toán)";
                $lines[] = "👨‍🎓 Học viên: $studentName";
                $lines[] = "🤝 CTV liên quan: $collaboratorName";
                $lines[] = "💰 Số tiền liên quan: $amount";
            } else {
                $lines[] = "📋 Nội dung: Cập nhật trạng thái hoa hồng";
                $lines[] = "👨‍🎓 Học viên: $studentName";
                $lines[] = "🔄 Trạng thái: " . self::translateStatus($old['status'] ?? '') . " → " . self::translateStatus($new['status']);
            }
        } elseif ($eventType === 'UPDATED' && $modelName === 'Payment' && isset($new['status'])) {
            $amount = number_format($this->auditable?->amount ?? 0, 0, ',', '.') . ' VNĐ';
            $lines[] = "📋 Nội dung: " . match ($new['status']) {
                'verified' => "Xác nhận đã thu tiền",
                'refunded' => "Hoàn trả tiền cho học viên",
                'cancelled' => "Hủy phiếu thu",
                default => "Cập nhật phiếu thu",
            };
            $lines[] = "👨‍🎓 Học viên: $studentName";
            $lines[] = "💰 Số tiền: $amount";
            $lines[] = "🔄 Trạng thái: " . self::translateStatus($old['status'] ?? '') . " → " . self::translateStatus($new['status']);
        } elseif ($eventType === 'UPDATED' && $modelName === 'Student' && isset($new['fee'])) {
            $oldFee = number_format($old['fee'] ?? 0, 0, ',', '.') . ' VNĐ';
            $newFee = number_format($new['fee'] ?? 0, 0, ',', '.') . ' VNĐ';
            $lines[] = "📋 Nội dung: Điều chỉnh học phí";
            $lines[] = "👨‍🎓 Học viên: $studentName";
            $lines[] = "💰 Học phí cũ: $oldFee";
            $lines[] = "💰 Học phí mới: $newFee";
        } elseif ($eventType === 'CREATED' && $modelName === 'Student') {
            $lines[] = "📋 Nội dung: Đăng ký học viên mới";
            $lines[] = "👨‍🎓 Học viên: $studentName";
        } elseif ($eventType === 'CREATED' && $modelName === 'Payment') {
            $amount = number_format($this->auditable?->amount ?? 0, 0, ',', '.') . ' VNĐ';
            $lines[] = "📋 Nội dung: Lập phiếu thu mới";
            $lines[] = "👨‍🎓 Học viên: $studentName";
            $lines[] = "💰 Số tiền: $amount";
        } elseif ($eventType === self::TYPE_BATCH_CONFIRMED) {
            $batchData = $this->metadata['batch_data'] ?? [];
            $totalAmount = $this->metadata['total_amount'] ?? 0;
            $count = count($batchData);
            
            $lines[] = "📋 Nội dung: CHỐT SỔ HÀNG LOẠT (Danh sách $count học viên)";
            $lines[] = "💰 Tổng số tiền chi: " . number_format($totalAmount, 0, ',', '.') . ' VNĐ';
            $lines[] = "";
            $lines[] = "📑 Danh sách chi tiết:";
            foreach ($batchData as $index => $item) {
                $idx = $index + 1;
                $sName = $item['student'] ?? 'N/A';
                $sAmt = number_format($item['amount'] ?? 0, 0, ',', '.') . ' VNĐ';
                $sCtv = $item['collaborator'] ?? 'N/A';
                $lines[] = "  $idx. $sName — $sAmt (CTV: $sCtv)";
            }
        } else {
            $lines[] = "📋 Nội dung: " . $this->getDetailedActionName();
        }

        if ($reason) {
            $lines[] = "📝 Lý do: $reason";
        }

        // Thông tin hàng loạt
        $meta = $new['meta'] ?? $old['meta'] ?? $this->metadata;
        if (is_array($meta) && isset($meta['batch_id'])) {
            $count = $meta['batch_count'] ?? 'nhiều';
            $lines[] = "📦 Đây là hành động HÀNG LOẠT thực hiện cho $count học viên cùng lúc.";
        }

        // Lịch sử hoàn tác (nếu có trong meta)
        $meta = $new['meta'] ?? $old['meta'] ?? null;
        if (is_array($meta) && isset($meta['rollback_history'])) {
            $lines[] = "";
            $lines[] = "📜 Lịch sử hoàn tác:";
            foreach ($meta['rollback_history'] as $h) {
                $hReason = $h['reason'] ?? 'N/A';
                $hAt = isset($h['at']) ? date('d/m/Y H:i', strtotime($h['at'])) : 'N/A';
                $hBy = $h['by'] ?? 'N/A';
                $lines[] = "  • $hReason — lúc $hAt bởi $hBy";
            }
        }

        return implode("\n", $lines);
    }

    /**
     * Dịch mã trạng thái sang tiếng Việt
     */
    public static function translateStatus(?string $status): string
    {
        return match ($status) {
            'paid' => 'Đã thanh toán',
            'payment_confirmed' => 'Đã chốt & Đã chi',
            'payable' => 'Có thể thanh toán',
            'pending' => 'Chờ xử lý',
            'verified' => 'Đã xác nhận',
            'refunded' => 'Đã hoàn trả',
            'cancelled' => 'Đã hủy',
            'received_confirmed' => 'CTV đã nhận tiền',
            default => $status ?? '—',
        };
    }

    /**
     * Chuyển đổi tên Model sang tiếng Việt và gắn kèm thông tin định danh
     */
    public function getFriendlyModelName(): string
    {
        $model = str_replace('App\\Models\\', '', $this->auditable_type);
        $studentName = $this->student?->full_name;

        return match ($model) {
            'Student' => "Học viên: " . ($studentName ?? $this->auditable_id),
            'Payment' => "Phiếu thu " . ($studentName ? "(Học viên: $studentName)" : "#$this->auditable_id"),
            'CommissionItem' => "Hoa hồng " . ($studentName ? "(Học viên: $studentName)" : "#$this->auditable_id"),
            'Collaborator' => "Cộng tác viên: " . ($this->auditable?->full_name ?? "#$this->auditable_id"),
            'User' => "Tài khoản: " . ($this->auditable?->name ?? "#$this->auditable_id"),
            'Intake' => "Đợt nhập học: " . ($this->auditable?->name ?? "#$this->auditable_id"),
            'Quota' => "Chỉ tiêu #$this->auditable_id",
            'Commission' => "Bảng hoa hồng " . ($studentName ? "($studentName)" : "#$this->auditable_id"),
            default => $model . " #$this->auditable_id",
        };
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    /**
     * Scope for Collaborator access: filter events related to their students
     */
    public function scopeForCollaborator($query, int $collaboratorId)
    {
        return $query->whereHas('student', function ($q) use ($collaboratorId) {
            $q->where('collaborator_id', $collaboratorId);
        });
    }
}
