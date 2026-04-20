<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\CommissionItem;
use App\Models\CommissionPolicy;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CommissionService {
    /**
     * Tạo commission khi Payment được xác nhận
     */
    public function createCommissionFromPayment(Payment $payment): Commission {
        return DB::transaction(function () use ($payment) {
            // 1. Tìm chính sách phù hợp
            $policy = $this->getMatchingPolicy($payment);

            // 2. Tạo commission chính (idempotent theo payment)
            $commission = Commission::firstOrCreate(
                ['payment_id' => $payment->id],
                [
                    'student_id' => $payment->student_id,
                    'rule' => [
                        'policy_id' => $policy?->id,
                        'program_type' => $payment->program_type,
                        'amount' => $payment->amount,
                        'payout_rules' => $policy?->payout_rules,
                    ],
                    'generated_at' => now(),
                ]
            );

            // 3. Tạo các dòng hoa hồng (Items) dựa trên quy tắc chia tiền (Split Rules)
            if ($policy && !empty($policy->payout_rules)) {
                $programKey = strtoupper($payment->program_type);
                $rules = $policy->payout_rules[$programKey] ?? ($policy->payout_rules['default'] ?? []);
                
                if (!empty($rules)) {
                    $this->createCommissionsFromRules($commission, $payment, $rules);
                } else {
                    $this->createDirectCommission($commission, $payment, CommissionItem::STATUS_PAYABLE);
                }
            } else {
                // Fallback nếu không có quy tắc split: tạo 1 dòng mặc định cho CTV chính
                $this->createDirectCommission($commission, $payment, CommissionItem::STATUS_PAYABLE);
            }

            return $commission;
        });
    }

    /**
     * Tạo commission trực tiếp cho CTV cấp 1 (Fallback)
     */
    private function createDirectCommission(Commission $commission, Payment $payment, string $initialStatus): void {
        $amount = $this->getDirectCommissionAmount($payment);

        $collaboratorId = $payment->primary_collaborator_id ?? ($payment->student->collaborator_id ?? null);

        if (!$collaboratorId) {
            Log::warning('Cannot create commission item: missing collaborator_id', [
                'payment_id' => $payment->id,
            ]);
            return;
        }

        // Tránh tạo trùng
        $exists = CommissionItem::where('commission_id', $commission->id)
            ->where('recipient_collaborator_id', $collaboratorId)
            ->where('role', 'direct')
            ->exists();
            
        if ($exists) return;

        CommissionItem::create([
            'commission_id' => $commission->id,
            'recipient_collaborator_id' => $collaboratorId,
            'role' => 'direct',
            'amount' => $amount,
            'status' => $initialStatus,
            'trigger' => 'payment_verified',
            'payable_at' => ($initialStatus === CommissionItem::STATUS_PAYABLE) ? now() : null,
            'visibility' => 'visible',
            'meta' => [
                'program_type' => $payment->program_type,
                'payment_id' => $payment->id,
            ],
        ]);
    }

    /**
     * Tạo các dòng hoa hồng từ danh sách quy tắc chia tiền
     */
    private function createCommissionsFromRules(Commission $commission, Payment $payment, array $rules): void {
        $directCollaboratorId = $payment->primary_collaborator_id ?? ($payment->student->collaborator_id ?? null);

        foreach ($rules as $rule) {
            $recipientId = null;

            if ($rule['recipient_type'] === 'direct_ctv') {
                $recipientId = $directCollaboratorId;
            } elseif ($rule['recipient_type'] === 'specific_ctv') {
                $recipientId = $rule['recipient_id'] ?? null;
            }

            if (!$recipientId) continue;

            // Xác định trạng thái ban đầu của hoa hồng
            // Nếu là trả sau khi nhập học -> STATUS_PENDING
            // Nếu là trả ngay mùng 5 -> STATUS_PAYABLE
            $initialStatus = ($rule['payout_trigger'] === 'student_enrolled') 
                ? CommissionItem::STATUS_PENDING 
                : CommissionItem::STATUS_PAYABLE;

            // Tránh tạo trùng dòng tiền giống hệt nhau cho cùng 1 người trong cùng 1 đợt
            $exists = CommissionItem::where('commission_id', $commission->id)
                ->where('recipient_collaborator_id', $recipientId)
                ->where('amount', $rule['amount_vnd'])
                ->where('trigger', $rule['payout_trigger'])
                ->exists();

            if ($exists) continue;

            CommissionItem::create([
                'commission_id' => $commission->id,
                'recipient_collaborator_id' => $recipientId,
                'role' => ($rule['recipient_type'] === 'direct_ctv') ? 'direct' : 'override',
                'amount' => (float)$rule['amount_vnd'],
                'status' => $initialStatus,
                'trigger' => $rule['payout_trigger'],
                'payable_at' => ($initialStatus === CommissionItem::STATUS_PAYABLE) ? now() : null,
                'visibility' => 'visible',
                'meta' => [
                    'description' => $rule['description'] ?? '',
                    'program_type' => $payment->program_type,
                    'payout_trigger_label' => $rule['payout_trigger'] === 'payment_verified' ? 'Mùng 5' : 'Nhập học',
                ],
            ]);
        }
    }

    /**
     * Lấy chính sách hoa hồng khớp nhất với hồ sơ
     */
    private function getMatchingPolicy(Payment $payment): ?CommissionPolicy {
        $programType = $payment->program_type;
        $studentMajor = $payment->student->major ?? null;
        $collaboratorId = $payment->primary_collaborator_id ?? ($payment->student->collaborator_id ?? null);

        return CommissionPolicy::where('active', true)
            ->where(function ($query) use ($studentMajor) {
                $query->whereNull('target_program_id')
                    ->orWhere('target_program_id', $studentMajor);
            })
            ->where(function ($query) use ($programType) {
                $query->whereNull('program_type')
                    ->orWhereJsonContains('program_type', strtoupper($programType))
                    ->orWhere('program_type', '[]'); // Case for empty array if null wasn't set
            })
            ->where(function ($query) use ($collaboratorId) {
                $query->whereNull('collaborator_id')
                    ->orWhere('collaborator_id', $collaboratorId);
            })
            ->orderBy('priority', 'desc')
            ->orderByRaw('collaborator_id DESC NULLS LAST')
            ->orderByRaw('target_program_id DESC NULLS LAST')
            ->first();
    }

    /**
     * Lấy số tiền hoa hồng trực tiếp (Fallback)
     */
    private function getDirectCommissionAmount(Payment $payment): float {
        $policy = $this->getMatchingPolicy($payment);
        $programType = $payment->program_type;

        if ($policy) {
            // Nếu có quy tắc chia tiền JSON, ưu tiên lấy dòng 'direct_ctv' trong đó
            if (!empty($policy->payout_rules)) {
                $programKey = strtoupper($programType);
                $rules = $policy->payout_rules[$programKey] ?? ($policy->payout_rules['default'] ?? []);
                
                $directRule = collect($rules)->firstWhere('recipient_type', 'direct_ctv');
                if ($directRule) {
                    return (float) ($directRule['amount_vnd'] ?? 0);
                }
            }

            // Legacy fallback cho các chính sách cũ (nếu còn)
            if ($policy->type === 'FIXED') {
                return (float) ($policy->amount_vnd ?? 0);
            }
            if ($policy->type === 'PERCENT') {
                return (float) $payment->amount * ((float) ($policy->percent ?? 0) / 100);
            }
        }

        // Fallback cứng cuối cùng theo hệ đào tạo (nếu không khớp bất kỳ chính sách nào)
        return match (strtolower($programType)) {
            'cq', 'regular' => 1750000,
            'vhvl', 'part_time' => 750000,
            'distance' => 500000,
            default => 0,
        };
    }

    /**
     * Nạp tiền vào wallet
     */
    private function depositToWallet(int $collaboratorId, float $amount, string $description): void {
        try {
            $wallet = Wallet::firstOrCreate(
                ['collaborator_id' => $collaboratorId],
                [
                    'balance' => 0,
                    'total_received' => 0,
                    'total_paid' => 0,
                ]
            );

            $wallet->deposit($amount, $description);
        } catch (\Throwable $e) {
            // Không để lỗi ví làm hỏng quá trình tạo commission
            Log::error('Wallet deposit failed', [
                'collaborator_id' => $collaboratorId,
                'amount' => $amount,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * CTV cấp 1 xác nhận đã nhận tiền → nạp ví và chuyển trạng thái
     */
    public function confirmDirectReceived(CommissionItem $item, int $userId): void {
        DB::transaction(function () use ($item, $userId) {
            // Lock row commission_item để ngăn việc gửi nhiều request đồng thời (Race Condition)
            $lockedItem = CommissionItem::where('id', $item->id)->lockForUpdate()->first();

            if (!$lockedItem) return;
            if ($lockedItem->role !== 'direct') return;
            if ($lockedItem->status !== CommissionItem::STATUS_PAYMENT_CONFIRMED) return;

            // Nạp tiền vào ví của CTV cấp 1
            $this->depositToWallet($lockedItem->recipient_collaborator_id, (float) $lockedItem->amount, 'CTV xác nhận nhận tiền (hoa hồng trực tiếp)');

            $lockedItem->markAsReceivedConfirmed($userId);
        });
    }

    /**
     * Cập nhật trạng thái hoa hồng khi sinh viên nhập học (Mở khóa các dòng PENDING)
     */
    public function unlockCommissionsOnEnrollment(Student $student): void {
        CommissionItem::whereHas('commission', function ($query) use ($student) {
            $query->where('student_id', $student->id);
        })
        ->where('trigger', 'student_enrolled')
        ->where('status', CommissionItem::STATUS_PENDING)
        ->update([
            'status' => CommissionItem::STATUS_PAYABLE,
            'payable_at' => now(),
        ]);
    }
}
