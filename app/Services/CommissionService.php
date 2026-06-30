<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\CommissionItem;
use App\Models\CommissionPolicy;
use App\Models\Payment;
use App\Models\Student;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class CommissionService {
    /**
     * Tạo commission khi Payment được xác nhận
     */
    public function createCommissionFromPayment(Payment $payment): ?Commission {
        $collaboratorId = $payment->primary_collaborator_id ?? ($payment->student->collaborator_id ?? null);
        if (!$collaboratorId) {
            return null;
        }

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
                $programKey = strtolower($payment->program_type);
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
                    ->orWhereJsonContains('program_type', strtolower($programType));
                
                // Thêm kiểm tra mảng trống một cách an toàn cho PostgreSQL
                if (DB::getDriverName() === 'pgsql') {
                    $query->orWhereRaw('jsonb_array_length(program_type::jsonb) = 0');
                } else {
                    $query->orWhere('program_type', '[]');
                }
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
                $programKey = strtolower($programType);
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
        return $this->getDirectCommissionAmountFallback($programType);
    }

    /**
     * Fallback cứng cuối cùng theo hệ đào tạo (nếu không khớp bất kỳ chính sách nào)
     */
    private function getDirectCommissionAmountFallback(?string $programType): float {
        return match (strtolower((string)$programType)) {
            'cq', 'regular' => 1750000,
            'vhvl', 'part_time' => 750000,
            'distance' => 200000,
            default => 0,
        };
    }

    /**
     * CTV cấp 1 xác nhận đã nhận tiền → chuyển trạng thái
     */
    public function confirmDirectReceived(CommissionItem $item, int $userId): void {
        DB::transaction(function () use ($item, $userId) {
            // Lock row commission_item để ngăn việc gửi nhiều request đồng thời (Race Condition)
            $lockedItem = CommissionItem::where('id', $item->id)->lockForUpdate()->first();

            if (!$lockedItem) return;
            if ($lockedItem->role !== 'direct') return;
            if ($lockedItem->status !== CommissionItem::STATUS_PAYMENT_CONFIRMED) return;

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

    /**
     * Tính toán lại hoa hồng khi sinh viên chuyển hệ / ngành
     */
    public function recalculateCommissionOnTransfer(Payment $payment): void {
        DB::transaction(function () use ($payment) {
            $commission = Commission::where('payment_id', $payment->id)->first();
            if (!$commission) return;

            // 1. Tìm chính sách mới
            $policy = $this->getMatchingPolicy($payment);
            
            // 2. Cập nhật luật trong commission chính
            $commission->update([
                'rule' => [
                    'policy_id' => $policy?->id,
                    'program_type' => $payment->program_type,
                    'amount' => $payment->amount,
                    'payout_rules' => $policy?->payout_rules,
                ],
            ]);

            // 3. Lấy tất cả items hiện có của commission này
            $items = $commission->items()->get();

            $newRules = [];
            if ($policy && !empty($policy->payout_rules)) {
                $programKey = strtolower((string)$payment->program_type);
                $newRules = $policy->payout_rules[$programKey] ?? ($policy->payout_rules['default'] ?? []);
            }

            // Nếu không tìm thấy rule nào từ policy, dùng fallback mặc định
            if (empty($newRules)) {
                $fallbackAmount = $this->getDirectCommissionAmountFallback($payment->program_type);
                if ($fallbackAmount > 0) {
                    $newRules = [
                        [
                            'recipient_type' => 'direct_ctv',
                            'amount_vnd' => $fallbackAmount,
                            'payout_trigger' => 'payment_verified',
                            'description' => 'Hoa hồng mặc định theo hệ'
                        ]
                    ];
                }
            }

            // Phân nhóm rules theo recipient
            $directCollaboratorId = $payment->primary_collaborator_id ?? ($payment->student->collaborator_id ?? null);

            $rulesByRecipient = [];
            foreach ($newRules as $rule) {
                $recipientId = null;
                if ($rule['recipient_type'] === 'direct_ctv') {
                    $recipientId = $directCollaboratorId;
                } elseif ($rule['recipient_type'] === 'specific_ctv') {
                    $recipientId = $rule['recipient_id'] ?? null;
                }
                if ($recipientId) {
                    $rulesByRecipient[$recipientId][] = $rule;
                }
            }

            foreach ($rulesByRecipient as $recipientId => $recipientRules) {
                // 1. Cancel all existing unpaid items and pending adjustments for this recipient
                $existingItems = $items->where('recipient_collaborator_id', $recipientId);
                foreach ($existingItems as $item) {
                    $isPaid = in_array($item->status, [
                        CommissionItem::STATUS_PAID,
                        CommissionItem::STATUS_PAYMENT_CONFIRMED,
                        CommissionItem::STATUS_RECEIVED_CONFIRMED
                    ]);
                    if (!$isPaid) {
                        $item->update(['status' => CommissionItem::STATUS_CANCELLED]);
                    }
                }

                $commission->adjustments()
                    ->where('recipient_collaborator_id', $recipientId)
                    ->where('status', CommissionItem::STATUS_PENDING)
                    ->update(['status' => CommissionItem::STATUS_CANCELLED]);

                // 2. Separate rules into Active and Pending
                $activeRules = [];
                $pendingRules = [];

                $isStudentEnrolled = ($payment->student?->status === Student::STATUS_ENROLLED);

                foreach ($recipientRules as $rule) {
                    $trigger = $rule['payout_trigger'] ?? 'payment_verified';
                    if ($trigger === 'payment_verified' || ($trigger === 'student_enrolled' && $isStudentEnrolled)) {
                        $activeRules[] = $rule;
                    } else {
                        $pendingRules[] = $rule;
                    }
                }

                // 3. Process Pending Rules (simply create pending adjustments)
                foreach ($pendingRules as $rule) {
                    \App\Models\CommissionAdjustment::create([
                        'commission_id' => $commission->id,
                        'recipient_collaborator_id' => $recipientId,
                        'amount' => (float)$rule['amount_vnd'],
                        'reason' => "Điều chỉnh hoa hồng (Đợi nhập học) do chuyển hệ sang " . $payment->program_type,
                        'status' => CommissionItem::STATUS_PENDING,
                        'created_by' => Auth::id(),
                    ]);
                }

                // 4. Process Active Rules (compare against net paid so far)
                $totalActiveAmount = 0;
                foreach ($activeRules as $rule) {
                    $totalActiveAmount += (float)$rule['amount_vnd'];
                }

                // Calculate total net paid so far
                $paidItemsSum = 0;
                foreach ($existingItems as $item) {
                    $isPaid = in_array($item->status, [
                        CommissionItem::STATUS_PAID,
                        CommissionItem::STATUS_PAYMENT_CONFIRMED,
                        CommissionItem::STATUS_RECEIVED_CONFIRMED
                    ]);
                    if ($isPaid) {
                        $paidItemsSum += (float)$item->amount;
                    }
                }

                $paidAdjustmentsSum = (float)$commission->adjustments()
                    ->where('recipient_collaborator_id', $recipientId)
                    ->whereNotIn('status', [CommissionItem::STATUS_PENDING, CommissionItem::STATUS_CANCELLED])
                    ->sum('amount');

                $totalNetPaid = $paidItemsSum + $paidAdjustmentsSum;

                // Adjust for the difference
                $difference = $totalActiveAmount - $totalNetPaid;
                if ($difference != 0) {
                    $adjustment = \App\Models\CommissionAdjustment::create([
                        'commission_id' => $commission->id,
                        'recipient_collaborator_id' => $recipientId,
                        'amount' => $difference,
                        'reason' => "Điều chỉnh hoa hồng do chuyển hệ sang " . $payment->program_type . " (Chênh lệch so với thực tế đã nhận)",
                        'status' => $difference < 0 ? 'received_confirmed' : 'payable',
                        'created_by' => Auth::id(),
                    ]);

                    if ($difference < 0) {
                        // Trừ trực tiếp trong hệ thống bằng cách ghi nhận CommissionAdjustment âm
                    }
                }
            }

            // Hủy các items của những người không còn thuộc newRules
            $newRecipients = array_keys($rulesByRecipient);

            foreach ($items as $item) {
                if (!in_array($item->recipient_collaborator_id, $newRecipients)) {
                    $isPaid = in_array($item->status, [
                        CommissionItem::STATUS_PAID,
                        CommissionItem::STATUS_PAYMENT_CONFIRMED,
                        CommissionItem::STATUS_RECEIVED_CONFIRMED
                    ]);
                    if (!$isPaid) {
                        $item->update(['status' => CommissionItem::STATUS_CANCELLED]);
                    } else {
                        // Đã thanh toán rồi nhưng giờ họ không được nhận nữa -> tạo adjustment âm thu hồi
                        $recipientId = $item->recipient_collaborator_id;
                        
                        $paidAdjustmentsSum = (float)$commission->adjustments()
                            ->where('recipient_collaborator_id', $recipientId)
                            ->whereNotIn('status', [CommissionItem::STATUS_PENDING, CommissionItem::STATUS_CANCELLED])
                            ->sum('amount');
                            
                        $totalNetPaid = (float)$item->amount + $paidAdjustmentsSum;
                        
                        if ($totalNetPaid > 0) {
                            $difference = -$totalNetPaid;
                            $adjustment = \App\Models\CommissionAdjustment::create([
                                'commission_id' => $commission->id,
                                'recipient_collaborator_id' => $recipientId,
                                'amount' => $difference,
                                'reason' => "Thu hồi hoa hồng do chuyển hệ học viên không còn thuộc chính sách chi trả",
                                'status' => 'received_confirmed',
                                'created_by' => Auth::id(),
                            ]);
                        }
                    }
                }
            }

            $commission->adjustments()
                ->whereNotIn('recipient_collaborator_id', $newRecipients)
                ->where('status', CommissionItem::STATUS_PENDING)
                ->update(['status' => CommissionItem::STATUS_CANCELLED]);
        });
    }
}
