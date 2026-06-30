<?php

namespace Tests\Feature;

use App\Models\Collaborator;
use App\Models\Payment;
use App\Models\PaymentAdjustment;
use App\Models\CommissionAdjustment;
use App\Models\Student;
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
            'status' => 'active',
        ]);

        $quotaId = DB::table('quotas')->insertGetId([
            'intake_id' => $intakeId,
            'name' => 'IT Quota',
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'REGULAR',
            'target_quota' => 10,
            'current_quota' => 0,
            'pending_quota' => 0,
            'reserved_quota' => 0,
            'status' => 'active',
        ]);

        // 2. Create collaborator
        $collaboratorId = DB::table('collaborators')->insertGetId([
            'full_name' => 'Nguyễn Văn A',
            'phone' => '0912345678',
            'email' => 'nva@example.com',
            'ref_id' => 'NVAREF',
            'status' => 'active',
        ]);

        // 3. Create student (REGULAR)
        $student = Student::factory()->create([
            'collaborator_id' => $collaboratorId,
            'quota_id' => $quotaId,
            'intake_id' => $intakeId,
            'program_type' => 'REGULAR',
            'major' => 'Công nghệ thông tin',
        ]);
        (new \App\Services\QuotaService())->handleStudentRegistration($student);

        // 4. Create payment
        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'primary_collaborator_id' => $collaboratorId,
            'program_type' => 'REGULAR',
            'amount' => 1750000,
            'status' => 'submitted',
        ]);

        // Verify Quota
        $quota = DB::table('quotas')->where('id', $quotaId)->first();
        $this->assertEquals(1, $quota->pending_quota);

        // Verify Payment Verified triggers quota consumption and commission creation
        $payment->update(['status' => 'verified']);

        $quota = DB::table('quotas')->where('id', $quotaId)->first();
        $this->assertEquals(0, $quota->pending_quota);
        $this->assertEquals(1, $quota->current_quota);

        $commissionService = new CommissionService();
        $commission = $commissionService->createCommissionFromPayment($payment);

        $this->assertNotNull($commission);
        $this->assertDatabaseHas('commissions', ['payment_id' => $payment->id]);

        // 5. CTV confirms receipt of commission
        $item = $commission->items()->where('role', 'direct')->first();
        $this->assertNotNull($item);
        
        // Simulating confirming payment and then confirming receipt
        $item->markAsPaymentConfirmed(null, 1);
        $commissionService->confirmDirectReceived($item, 1);

        // Verify CommissionItem status is updated
        $item->refresh();
        $this->assertEquals(\App\Models\CommissionItem::STATUS_RECEIVED_CONFIRMED, $item->status);

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
        $this->assertEquals(-1000000, (float)$adj->amount);
    }
}
