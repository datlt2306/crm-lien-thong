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
            // Tạo commission chính (idempotent theo payment)
            $commission = Commission::firstOrCreate(
                ['payment_id' => $payment->id],
                [
                    'student_id' => $payment->student_id,
                    'rule' => [
                        'program_type' => $payment->program_type,
                        'amount' => $payment->amount,
                    ],
                    'generated_at' => now(),
                ]
            );

            // Tạo commission cho CTV cấp 1 (direct) ở trạng thái PAYABLE
            // Chủ đơn vị sẽ "Xác nhận thanh toán" (upload bill) để chuyển item sang PAYMENT_CONFIRMED,
            // sau đó CTV cấp 1 mới xác nhận đã nhận để nạp ví.
            $this->createDirectCommission($commission, $payment, CommissionItem::STATUS_PAYABLE);

            // Không còn CTV cấp 2 - đã loại bỏ logic downline commission

            return $commission;
        });
    }

    // createCommissionOnSubmission đã được loại bỏ theo flow mới (chỉ tạo sau VERIFY)

    /**
     * Tạo commission trực tiếp cho CTV cấp 1
     */
    private function createDirectCommission(Commission $commission, Payment $payment, string $initialStatus): void {
        $amount = $this->getDirectCommissionAmount($payment);

        // Lấy collaborator_id: ưu tiên từ payment->primary_collaborator_id, nếu không có thì lấy từ student->collaborator_id
        $collaboratorId = $payment->primary_collaborator_id;
        if (!$collaboratorId && $payment->student) {
            $collaboratorId = $payment->student->collaborator_id;
        }

        // Nếu vẫn không có collaborator_id, không thể tạo commission_item
        if (!$collaboratorId) {
            Log::warning('Cannot create commission item: missing collaborator_id', [
                'payment_id' => $payment->id,
                'student_id' => $payment->student_id,
            ]);
            return;
        }

        // Tránh tạo trùng (idempotent)
        $exists = CommissionItem::where('commission_id', $commission->id)
            ->where('recipient_collaborator_id', $collaboratorId)
            ->where('role', 'direct')
            ->exists();
        if ($exists) {
            return;
        }

        CommissionItem::create([
            'commission_id' => $commission->id,
            'recipient_collaborator_id' => $collaboratorId,
            'role' => 'direct',
            'amount' => $amount,
            'status' => $initialStatus,
            'trigger' => 'payment_verified',
            'payable_at' => now(),
            'visibility' => 'visible',
            'meta' => [
                'program_type' => $payment->program_type,
                'payment_id' => $payment->id,
            ],
        ]);
        // Không nạp tiền ví ngay ở bước SUBMITTED/CONFIRMED. Chờ CTV cấp 1 xác nhận nhận tiền.
    }

    // ===== LOẠI BỎ LOGIC CTV CẤP 2 - HỆ THỐNG CHỈ CÒN 1 CẤP =====
    // createDownlineCommission() đã bị xóa

    // ===== LOẠI BỎ LOGIC CTV CẤP 2 - updateCommissionsOnEnrollment() đã bị xóa =====

    // ===== LOẠI BỎ LOGIC CTV CẤP 2 - transferCommissionToDownline() đã bị xóa =====

    /**
     * Lấy số tiền commission trực tiếp theo hệ đào tạo
     */
    private function getDirectCommissionAmount(Payment $payment): float {
        $programType = $payment->program_type;
        $now = now();
        $collaboratorId = $payment->primary_collaborator_id ?? ($payment->student->collaborator_id ?? null);

        // Lấy chính sách có độ ưu tiên cao nhất, vẫn còn hiệu lực và đúng điều kiện
        $policy = \App\Models\CommissionPolicy::where('role', 'PRIMARY')
            ->where('active', true)
            ->where(function ($query) use ($programType) {
                $query->whereNull('program_type')
                    ->orWhere('program_type', strtoupper($programType));
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('effective_from')
                    ->orWhere('effective_from', '<=', $now);
            })
            ->where(function ($query) use ($now) {
                $query->whereNull('effective_to')
                    ->orWhere('effective_to', '>=', $now);
            })
            ->where(function ($query) use ($collaboratorId) {
                $query->whereNull('collaborator_id')
                    ->orWhere('collaborator_id', $collaboratorId);
            })
            ->orderBy('priority', 'desc')
            ->first();

        if ($policy) {
            if ($policy->type === 'PASS_THROUGH') {
                return (float) $payment->amount;
            }
            if ($policy->type === 'FIXED') {
                return (float) ($policy->amount_vnd ?? 0);
            }
            if ($policy->type === 'PERCENT') {
                return (float) $payment->amount * ((float) ($policy->percent ?? 0) / 100);
            }
        }

        // Fallback cứng nếu không có chính sách
        return match (strtolower($programType)) {
            'cq', 'regular' => 1750000,
            'vhvlv', 'part_time' => 750000,
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

    // ===== LOẠI BỎ LOGIC CTV CẤP 2 =====
    // confirmDownlineTransfer() và confirmDownlineReceived() đã bị xóa
}
