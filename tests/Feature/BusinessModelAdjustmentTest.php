<?php

namespace Tests\Feature;

use App\Models\Collaborator;
use App\Models\Payment;
use App\Models\PaymentAdjustment;
use App\Models\CommissionAdjustment;
use App\Models\Student;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BusinessModelAdjustmentTest extends TestCase {
    use RefreshDatabase;

    public function test_payment_and_commission_adjustments_are_generated_on_student_transfer() {
        // 1. Create intake and quota
        $intakeId = DB::table('intakes')->insertGetId([
            'name' => 'Đợt 1',
            'description' => 'Test intake',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'enrollment_deadline' => '2026-12-31',
            'status' => 'active',
            'organization_id' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotaId1 = DB::table('quotas')->insertGetId([
            'intake_id' => $intakeId,
            'name' => 'CNTT - CQ',
            'organization_id' => 1,
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'REGULAR',
            'target_quota' => 10,
            'current_quota' => 1,
            'pending_quota' => 0,
            'reserved_quota' => 0,
            'tuition_fee' => 1750000,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotaId2 = DB::table('quotas')->insertGetId([
            'intake_id' => $intakeId,
            'name' => 'CNTT - VLVH',
            'organization_id' => 1,
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'PART_TIME',
            'target_quota' => 10,
            'current_quota' => 1,
            'pending_quota' => 0,
            'reserved_quota' => 0,
            'tuition_fee' => 750000,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create collaborator
        $collaboratorId = DB::table('collaborators')->insertGetId([
            'full_name' => 'CTV A',
            'phone' => '0912345678',
            'email' => 'ctv.a@example.com',
            'organization_id' => 1,
            'ref_id' => 'CTVAREF',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $collaborator = Collaborator::find($collaboratorId);

        // 3. Create student and payment
        $student = Student::factory()->create([
            'organization_id' => 1,
            'collaborator_id' => $collaboratorId,
            'quota_id' => $quotaId1,
            'intake_id' => $intakeId,
            'program_type' => 'REGULAR',
            'major' => 'Công nghệ thông tin',
        ]);

        $payment = Payment::factory()->create([
            'organization_id' => 1,
            'student_id' => $student->id,
            'primary_collaborator_id' => $collaboratorId,
            'status' => 'verified',
            'amount' => 1750000,
            'program_type' => 'REGULAR',
        ]);

        // 4. Force trigger the commission generation (if not already done by observer)
        $commissionService = new CommissionService();
        $commission = $commissionService->createCommissionFromPayment($payment);

        $this->assertNotNull($commission);
        $this->assertDatabaseHas('commissions', ['payment_id' => $payment->id]);

        // 5. CTV confirms receipt of commission (adds to wallet)
        $item = $commission->items()->where('role', 'direct')->first();
        $this->assertNotNull($item);
        
        // Simulating confirming payment and then confirming receipt
        $item->markAsPaymentConfirmed(null, 1);
        $commissionService->confirmDirectReceived($item, 1);

        // Verify Wallet and transaction
        $wallet = Wallet::where('collaborator_id', $collaboratorId)->first();
        $this->assertNotNull($wallet);
        $this->assertEquals((float)$item->amount, (float)$wallet->balance);

        $this->assertDatabaseHas('wallet_transactions', [
            'wallet_id' => $wallet->id,
            'type' => 'deposit',
            'amount' => $item->amount,
        ]);

        // 6. Simulate Student Transfer (CQ 1.75M -> VLVH 750k)
        $feeDifference = 1750000 - 750000; // 1,000,000đ

        // Create PaymentAdjustment
        $paymentAdjustment = PaymentAdjustment::create([
            'payment_id' => $payment->id,
            'student_id' => $student->id,
            'type' => 'transfer',
            'amount' => $feeDifference,
            'reason' => 'Chênh lệch phí chuyển hệ',
            'refund_status' => 'pending',
            'created_by' => 1,
        ]);

        $this->assertDatabaseHas('payment_adjustments', [
            'payment_id' => $payment->id,
            'amount' => $feeDifference,
            'refund_status' => 'pending',
        ]);

        // Update payment program type and recalculate commission
        $payment->update(['program_type' => 'PART_TIME']);
        $commissionService->recalculateCommissionOnTransfer($payment);

        // Verify that commission adjustment is created
        $this->assertEquals(1, \App\Models\CommissionAdjustment::count());
        $adj = \App\Models\CommissionAdjustment::first();
        $this->assertEquals($commission->id, $adj->commission_id);
        $this->assertEquals($collaboratorId, $adj->recipient_collaborator_id);
        $this->assertEquals('received_confirmed', $adj->status);

        // Wallet balance should be decremented due to negative difference
        $wallet->refresh();
        $expectedNewBalance = 1750000 - 1000000; // Original (CQ fallback) - difference (1M) = 750k
        // Wait, the fallback direct commission for regular (CQ) is 1.75M and for VLVH is 750k.
        // Difference is -1M. So wallet balance should decrease by 1M.
        $this->assertEquals(750000, (float)$wallet->balance);

        // Verify transaction is logged
        $this->assertEquals(2, \App\Models\WalletTransaction::count()); // 1 commission + 1 chargeback
        $tx = \App\Models\WalletTransaction::where('type', 'withdrawal')->first();
        $this->assertNotNull($tx);
        $this->assertEquals(-1000000, (float)$tx->amount);
    }
}
