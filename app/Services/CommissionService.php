<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\CommissionItem;
use App\Models\DownlineCommissionConfig;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
                    'organization_id' => $payment->organization_id,
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

            // Tạo commission cho CTV cấp 2 (nếu có)
            $this->createDownlineCommission($commission, $payment);

            return $commission;
        });
    }

    // createCommissionOnSubmission đã được loại bỏ theo flow mới (chỉ tạo sau VERIFY)

    /**
     * Tạo commission trực tiếp cho CTV cấp 1
     */
    private function createDirectCommission(Commission $commission, Payment $payment, string $initialStatus): void {
        $amount = $this->getDirectCommissionAmount($payment);

        // Tránh tạo trùng (idempotent)
        $exists = CommissionItem::where('commission_id', $commission->id)
            ->where('recipient_collaborator_id', $payment->primary_collaborator_id)
            ->where('role', 'direct')
            ->exists();
        if ($exists) {
            return;
        }

        CommissionItem::create([
            'commission_id' => $commission->id,
            'recipient_collaborator_id' => $payment->primary_collaborator_id,
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

    /**
     * Tạo commission cho CTV cấp 2
     */
    public function createDownlineCommission(Commission $commission, Payment $payment): ?CommissionItem {
        // Kiểm tra xem student có ref_id của CTV cấp 2 không
        $student = $payment->student;
        if (!$student || !$student->collaborator_id) {
            return null;
        }

        // Tìm CTV cấp 2
        $downlineCollaborator = $student->collaborator;
        if (!$downlineCollaborator || !$downlineCollaborator->upline_id) {
            return null;
        }

        // Kiểm tra xem CTV cấp 2 có phải con của CTV cấp 1 không
        if ($downlineCollaborator->upline_id !== $payment->primary_collaborator_id) {
            return null;
        }

        // Lấy cấu hình hoa hồng
        $config = DownlineCommissionConfig::where('upline_collaborator_id', $payment->primary_collaborator_id)
            ->where('downline_collaborator_id', $downlineCollaborator->id)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            return null;
        }

        $amount = $config->getAmountByProgramType($payment->program_type);
        if ($amount <= 0) {
            return null;
        }

        // Xác định trạng thái dựa trên hình thức thanh toán
        $status = $config->isImmediatePayment()
            ? CommissionItem::STATUS_PAYABLE
            : CommissionItem::STATUS_PENDING;

        $item = CommissionItem::create([
            'commission_id' => $commission->id,
            'recipient_collaborator_id' => $downlineCollaborator->id,
            'role' => 'downline',
            'amount' => $amount,
            'status' => $status,
            'trigger' => $config->isImmediatePayment() ? 'payment_verified' : 'student_enrolled',
            'payable_at' => $config->isImmediatePayment() ? now() : null,
            'visibility' => 'visible',
            'meta' => [
                'program_type' => $payment->program_type,
                'payment_id' => $payment->id,
                'upline_collaborator_id' => $payment->primary_collaborator_id,
                'config_id' => $config->id,
            ],
        ]);
        return $item;
    }

    /**
     * Cập nhật commission khi student nhập học
     */
    public function updateCommissionsOnEnrollment(Student $student): void {
        DB::transaction(function () use ($student) {
            // Tìm tất cả commission items pending của CTV cấp 2 liên quan đến student này
            $pendingItems = CommissionItem::where('recipient_collaborator_id', $student->collaborator_id)
                ->where('status', CommissionItem::STATUS_PENDING)
                ->where('trigger', 'student_enrolled')
                ->whereJsonContains('meta->program_type', $student->source ?? 'cq')
                ->get();

            foreach ($pendingItems as $item) {
                $item->update([
                    'status' => CommissionItem::STATUS_PAYABLE,
                    'payable_at' => now(),
                ]);

                // Chuyển tiền từ wallet CTV cấp 1 sang CTV cấp 2
                $this->transferCommissionToDownline($item);
            }
        });
    }

    /**
     * Chuyển tiền commission từ CTV cấp 1 sang CTV cấp 2
     */
    private function transferCommissionToDownline(CommissionItem $item): void {
        $uplineId = $item->meta['upline_collaborator_id'] ?? null;
        if (!$uplineId) {
            return;
        }

        $uplineWallet = Wallet::where('collaborator_id', $uplineId)->first();
        $downlineWallet = Wallet::where('collaborator_id', $item->recipient_collaborator_id)->first();

        if (!$uplineWallet || !$downlineWallet) {
            return;
        }

        // Chuyển tiền từ wallet CTV cấp 1 sang CTV cấp 2
        $uplineWallet->transferTo(
            $downlineWallet,
            $item->amount,
            "Hoa hồng cho CTV cấp 2 - {$item->recipient->full_name}",
            [
                'commission_item_id' => $item->id,
                'student_id' => $item->commission->student_id,
            ]
        );
    }

    /**
     * Lấy số tiền commission trực tiếp theo hệ đào tạo
     */
    private function getDirectCommissionAmount(Payment $payment): float {
        $programType = $payment->program_type;

        // Tìm chính sách hoa hồng phù hợp
        $policy = \App\Models\CommissionPolicy::where('type', 'PASS_THROUGH')
            ->where('role', 'PRIMARY')
            ->where('active', true)
            ->where(function ($query) use ($programType) {
                $query->whereNull('program_type')
                    ->orWhere('program_type', strtoupper($programType));
            })
            ->orderBy('priority', 'desc')
            ->first();

        if ($policy) {
            // PASS_THROUGH: nhận toàn bộ số tiền thanh toán
            return (float) $payment->amount;
        }

        // Fallback: tìm chính sách cố định
        $fixedPolicy = \App\Models\CommissionPolicy::where('type', 'FIXED')
            ->where('role', 'PRIMARY')
            ->where('active', true)
            ->where(function ($query) use ($programType) {
                $query->whereNull('program_type')
                    ->orWhere('program_type', strtoupper($programType));
            })
            ->orderBy('priority', 'desc')
            ->first();

        if ($fixedPolicy) {
            return (float) $fixedPolicy->amount_vnd ?? 0;
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
        if ($item->role !== 'direct') return;
        if ($item->status !== CommissionItem::STATUS_PAYMENT_CONFIRMED) return;

        // Nạp tiền vào ví của CTV cấp 1
        $this->depositToWallet($item->recipient_collaborator_id, (float) $item->amount, 'CTV xác nhận nhận tiền (hoa hồng trực tiếp)');

        $item->markAsReceivedConfirmed($userId);
    }

    /**
     * Chủ đơn vị chuyển tiền cho CTV cấp 2 khi đến hạn
     */
    public function confirmDownlineTransfer(CommissionItem $downlineItem): void {
        if ($downlineItem->role !== 'downline') return;
        // Chỉ xử lý khi đã đến hạn (payable) hoặc đã payment confirmed
        if (!in_array($downlineItem->status, [CommissionItem::STATUS_PAYABLE, CommissionItem::STATUS_PAYMENT_CONFIRMED])) return;

        $this->transferCommissionToDownline($downlineItem);
        $downlineItem->markAsReceivedConfirmed(auth()->id() ?? 0);
    }
}
