<?php

namespace App\Services;

use App\Models\Commission;
use App\Models\CommissionItem;
use App\Models\DownlineCommissionConfig;
use App\Models\Payment;
use App\Models\Student;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class CommissionService {
    /**
     * Tạo commission khi Payment được xác nhận
     */
    public function createCommissionFromPayment(Payment $payment): Commission {
        return DB::transaction(function () use ($payment) {
            // Tạo commission chính
            $commission = Commission::create([
                'organization_id' => $payment->organization_id,
                'payment_id' => $payment->id,
                'student_id' => $payment->student_id,
                'rule' => [
                    'program_type' => $payment->program_type,
                    'amount' => $payment->amount,
                ],
                'generated_at' => now(),
            ]);

            // Tạo commission cho CTV cấp 1 (direct)
            $this->createDirectCommission($commission, $payment);

            // Tạo commission cho CTV cấp 2 (nếu có)
            $this->createDownlineCommission($commission, $payment);

            return $commission;
        });
    }

    /**
     * Tạo commission trực tiếp cho CTV cấp 1
     */
    private function createDirectCommission(Commission $commission, Payment $payment): void {
        $amount = $this->getDirectCommissionAmount($payment->program_type);

        CommissionItem::create([
            'commission_id' => $commission->id,
            'recipient_collaborator_id' => $payment->primary_collaborator_id,
            'role' => 'direct',
            'amount' => $amount,
            'status' => CommissionItem::STATUS_PAYABLE,
            'trigger' => 'payment_verified',
            'payable_at' => now(),
            'visibility' => 'visible',
            'meta' => [
                'program_type' => $payment->program_type,
                'payment_id' => $payment->id,
            ],
        ]);

        // Nạp tiền vào wallet của CTV cấp 1
        $this->depositToWallet($payment->primary_collaborator_id, $amount, "Hoa hồng trực tiếp - {$payment->program_type}");
    }

    /**
     * Tạo commission cho CTV cấp 2
     */
    private function createDownlineCommission(Commission $commission, Payment $payment): void {
        // Kiểm tra xem student có ref_id của CTV cấp 2 không
        $student = $payment->student;
        if (!$student || !$student->collaborator_id) {
            return;
        }

        // Tìm CTV cấp 2
        $downlineCollaborator = $student->collaborator;
        if (!$downlineCollaborator || !$downlineCollaborator->upline_id) {
            return;
        }

        // Kiểm tra xem CTV cấp 2 có phải con của CTV cấp 1 không
        if ($downlineCollaborator->upline_id !== $payment->primary_collaborator_id) {
            return;
        }

        // Lấy cấu hình hoa hồng
        $config = DownlineCommissionConfig::where('upline_collaborator_id', $payment->primary_collaborator_id)
            ->where('downline_collaborator_id', $downlineCollaborator->id)
            ->where('is_active', true)
            ->first();

        if (!$config) {
            return;
        }

        $amount = $config->getAmountByProgramType($payment->program_type);
        if ($amount <= 0) {
            return;
        }

        // Xác định trạng thái dựa trên hình thức thanh toán
        $status = $config->isImmediatePayment()
            ? CommissionItem::STATUS_PAYABLE
            : CommissionItem::STATUS_PENDING;

        CommissionItem::create([
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
     * Lấy số tiền commission trực tiếp theo loại chương trình
     */
    private function getDirectCommissionAmount(string $programType): float {
        // Đọc theo cấu hình bảng programs nếu có
        $program = \App\Models\Program::where('code', strtoupper($programType))->first();
        if ($program && $program->direct_commission_amount > 0) {
            return (float) $program->direct_commission_amount;
        }
        // Fallback cứng
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
        $wallet = Wallet::firstOrCreate(
            ['collaborator_id' => $collaboratorId],
            [
                'balance' => 0,
                'total_received' => 0,
                'total_paid' => 0,
            ]
        );

        $wallet->deposit($amount, $description);
    }
}
