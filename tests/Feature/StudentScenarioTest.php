<?php

namespace Tests\Feature;

use App\Models\Collaborator;
use App\Models\Payment;
use App\Models\PaymentAdjustment;
use App\Models\CommissionAdjustment;
use App\Models\Student;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\CommissionPolicy;
use App\Models\CommissionItem;
use App\Services\CommissionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class StudentScenarioTest extends TestCase {
    use RefreshDatabase;

    /**
     * Scenario 1:
     * - Student Kim Hồng Phong, born 21/10/2005, origin Nghệ An, phone 0868266410, CCCD 040205013484.
     * - Registers for Regular (Chính quy) in June 2026.
     * - GTVT paid commission to Lê Trọng Đạt.
     * - After 2 months, quota is full.
     * - Switches to VHVL in June 2026.
     */
    public function test_scenario_kim_hong_phong_transfer_to_vhvl() {
        // 1. Create intake for June 2026
        $intakeId = DB::table('intakes')->insertGetId([
            'name' => 'Đợt Tháng 6/2026',
            'description' => 'Tuyển sinh tháng 6/2026',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'enrollment_deadline' => '2026-06-30',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create Quota for Regular (Chính quy) and Part-Time (VHVL)
        $quotaCqId = DB::table('quotas')->insertGetId([
            'intake_id' => $intakeId,
            'name' => 'CNTT - CQ',
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'REGULAR',
            'target_quota' => 1,
            'current_quota' => 0,
            'pending_quota' => 0,
            'reserved_quota' => 0,
            'tuition_fee' => 1750000,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotaVhvlId = DB::table('quotas')->insertGetId([
            'intake_id' => $intakeId,
            'name' => 'CNTT - VHVL',
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'PART_TIME',
            'target_quota' => 10,
            'current_quota' => 0,
            'pending_quota' => 0,
            'reserved_quota' => 0,
            'tuition_fee' => 750000,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Create collaborator Lê Trọng Đạt
        $collaboratorId = DB::table('collaborators')->insertGetId([
            'full_name' => 'Lê Trọng Đạt',
            'phone' => '0987654321',
            'email' => 'datletrong2306@gmail.com',
            'ref_id' => 'LETRONGDAT',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $collaborator = Collaborator::find($collaboratorId);

        // 4. Create Commission Policy for Lê Trọng Đạt (based on CommissionPolicySeeder)
        CommissionPolicy::create([
            'collaborator_id' => $collaboratorId,
            'program_type' => ['regular', 'part_time', 'distance'],
            'role' => 'primary',
            'type' => 'fixed',
            'payout_rules' => [
                'regular' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 1750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng tuyển sinh chính quy']
                ],
                'part_time' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng đợt 1 (Xác nhận phí)'],
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 1450000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng đợt 2 (Nhập học)']
                ],
                'distance' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng đợt 1 (Xác nhận phí)'],
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 1450000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng đợt 2 (Nhập học)']
                ]
            ],
            'trigger' => 'on_verification',
            'visibility' => 'internal',
            'priority' => 10,
            'active' => true,
        ]);

        // 5. Create student Kim Hồng Phong (REGULAR)
        $student = Student::factory()->create([
            'full_name' => 'Kim Hồng Phong',
            'dob' => '2005-10-21',
            'birth_place' => 'Nghệ An',
            'phone' => '0868266410',
            'identity_card' => '040205013484',
            'collaborator_id' => $collaboratorId,
            'quota_id' => $quotaCqId,
            'intake_id' => $intakeId,
            'program_type' => 'REGULAR',
            'major' => 'Công nghệ thông tin',
        ]);

        // 6. Create payment and verify
        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'primary_collaborator_id' => $collaboratorId,
            'program_type' => 'REGULAR',
            'amount' => 1750000,
            'status' => 'submitted',
        ]);

        // Verify the payment to trigger quota deduction and commission creation
        $payment->update(['status' => 'verified']);

        // Check Quota CQ is consumed
        $this->assertEquals(1, DB::table('quotas')->where('id', $quotaCqId)->value('current_quota'));

        // Check Commission generated
        $commission = $payment->fresh()->commission;
        $this->assertNotNull($commission);

        // Đã chuyển tiền hoa hồng cho người giới thiệu rồi (Status = payment_confirmed and received_confirmed)
        $item = $commission->items()->where('role', 'direct')->first();
        $this->assertNotNull($item);
        $this->assertEquals(1750000, (float)$item->amount);

        $item->markAsPaymentConfirmed(null, 1);
        $commissionService = new CommissionService();
        $commissionService->confirmDirectReceived($item, 1);

        // Wallet of Lê Trọng Đạt should have 1,750,000đ
        $wallet = Wallet::where('collaborator_id', $collaboratorId)->first();
        $this->assertNotNull($wallet);
        $this->assertEquals(1750000, (float)$wallet->balance);

        // 7. Simulate Quota CQ becomes full (hết chỉ tiêu)
        // Here, current_quota is 1 and target_quota is 1. So it is full.
        $this->assertEquals(
            DB::table('quotas')->where('id', $quotaCqId)->value('current_quota'),
            DB::table('quotas')->where('id', $quotaCqId)->value('target_quota')
        );

        // 8. Transfer student to VHVL (PART_TIME)
        // Update student quota, program type, and has_transferred flag
        $student->update([
            'quota_id' => $quotaVhvlId,
            'program_type' => 'PART_TIME',
            'has_transferred' => true,
        ]);

        // Update payment program type and recalculate commission
        $payment->update(['program_type' => 'PART_TIME']);
        $commissionService->recalculateCommissionOnTransfer($payment);

        // Verify Quota transfer
        $this->assertEquals(0, DB::table('quotas')->where('id', $quotaCqId)->value('current_quota'));
        $this->assertEquals(1, DB::table('quotas')->where('id', $quotaVhvlId)->value('current_quota'));

        // Verify Commission Adjustments
        // The recalculation should compare VHVL rules against paid CQ:
        // Rule 1 (Active): 750,000đ (payout_verified). Difference = 750,000 - 1,750,000 = -1,000,000đ (immediate wallet deduction)
        // Rule 2 (Pending): 1,450,000đ (student_enrolled). Created as a pending adjustment.
        $adjustments = CommissionAdjustment::where('commission_id', $commission->id)->get();
        $this->assertEquals(2, $adjustments->count());

        $this->assertTrue($adjustments->contains('amount', -1000000));
        $this->assertTrue($adjustments->contains('amount', 1450000));

        // Wallet balance should be adjusted: 1,750,000 - 1,000,000 = 750,000đ
        $wallet->refresh();
        $this->assertEquals(750000, (float)$wallet->balance);
    }

    /**
     * Scenario 2:
     * - Student registers Regular (Chính quy).
     * - Commission is paid.
     * - Transfers to VHVL.
     * - Then transfers to Distance (Từ xa / DISTANCE).
     */
    public function test_scenario_double_transfer_cq_to_vhvl_to_distance() {
        // 1. Create intake for June 2026
        $intakeId = DB::table('intakes')->insertGetId([
            'name' => 'Đợt Tháng 6/2026',
            'description' => 'Tuyển sinh tháng 6/2026',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'enrollment_deadline' => '2026-06-30',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create Quotas
        $quotaCqId = DB::table('quotas')->insertGetId([
            'intake_id' => $intakeId,
            'name' => 'CNTT - CQ',
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'REGULAR',
            'target_quota' => 10,
            'current_quota' => 0,
            'pending_quota' => 0,
            'reserved_quota' => 0,
            'tuition_fee' => 1750000,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotaVhvlId = DB::table('quotas')->insertGetId([
            'intake_id' => $intakeId,
            'name' => 'CNTT - VHVL',
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'PART_TIME',
            'target_quota' => 10,
            'current_quota' => 0,
            'pending_quota' => 0,
            'reserved_quota' => 0,
            'tuition_fee' => 750000,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotaDistanceId = DB::table('quotas')->insertGetId([
            'intake_id' => $intakeId,
            'name' => 'CNTT - TX',
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'DISTANCE',
            'target_quota' => 10,
            'current_quota' => 0,
            'pending_quota' => 0,
            'reserved_quota' => 0,
            'tuition_fee' => 500000,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Create collaborator Lê Trọng Đạt
        $collaboratorId = DB::table('collaborators')->insertGetId([
            'full_name' => 'Lê Trọng Đạt',
            'phone' => '0987654321',
            'email' => 'datletrong2306@gmail.com',
            'ref_id' => 'LETRONGDAT',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $collaborator = Collaborator::find($collaboratorId);

        // 4. Create Commission Policy
        CommissionPolicy::create([
            'collaborator_id' => $collaboratorId,
            'program_type' => ['regular', 'part_time', 'distance'],
            'role' => 'primary',
            'type' => 'fixed',
            'payout_rules' => [
                'regular' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 1750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng tuyển sinh chính quy']
                ],
                'part_time' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng đợt 1 (Xác nhận phí)'],
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 1450000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng đợt 2 (Nhập học)']
                ],
                'distance' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 500000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng đợt 1 từ xa'],
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 1000000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng đợt 2 từ xa']
                ]
            ],
            'trigger' => 'on_verification',
            'visibility' => 'internal',
            'priority' => 10,
            'active' => true,
        ]);

        // 5. Create student (REGULAR)
        $student = Student::factory()->create([
            'collaborator_id' => $collaboratorId,
            'quota_id' => $quotaCqId,
            'intake_id' => $intakeId,
            'program_type' => 'REGULAR',
            'major' => 'Công nghệ thông tin',
        ]);

        // 6. Create payment and verify
        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'primary_collaborator_id' => $collaboratorId,
            'program_type' => 'REGULAR',
            'amount' => 1750000,
            'status' => 'submitted',
        ]);
        $payment->update(['status' => 'verified']);

        // Confirm paid
        $commission = $payment->fresh()->commission;
        $item = $commission->items()->where('role', 'direct')->first();
        $item->markAsPaymentConfirmed(null, 1);
        $commissionService = new CommissionService();
        $commissionService->confirmDirectReceived($item, 1);

        $wallet = Wallet::where('collaborator_id', $collaboratorId)->first();
        $this->assertEquals(1750000, (float)$wallet->balance);

        // First transfer: CQ -> VHVL (PART_TIME)
        $student->update([
            'quota_id' => $quotaVhvlId,
            'program_type' => 'PART_TIME',
            'has_transferred' => true,
        ]);
        $payment->update(['program_type' => 'PART_TIME']);
        $commissionService->recalculateCommissionOnTransfer($payment);

        $wallet->refresh();
        $this->assertEquals(750000, (float)$wallet->balance); // 1.75M - 1M = 750k

        // Second transfer: VHVL -> Distance (DISTANCE)
        $student->update([
            'quota_id' => $quotaDistanceId,
            'program_type' => 'DISTANCE',
            'has_transferred' => true,
        ]);
        $payment->update(['program_type' => 'DISTANCE']);
        
        $commissionService->recalculateCommissionOnTransfer($payment);

        // Let's reload wallet balance and see the result
        $wallet->refresh();
        $this->assertEquals(500000, (float)$wallet->balance); // 1.75M - 1M - 250k = 500k
    }

    public function test_happy_path_registration_to_enrollment_for_each_program_type() {
        $commissionService = new CommissionService();

        // 1. Create intake for June 2026
        $intakeId = DB::table('intakes')->insertGetId([
            'name' => 'Đợt Tháng 6/2026',
            'description' => 'Tuyển sinh tháng 6/2026',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'enrollment_deadline' => '2026-06-30',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create collaborator Lê Trọng Đạt
        $collaboratorId = DB::table('collaborators')->insertGetId([
            'full_name' => 'Lê Trọng Đạt',
            'phone' => '0987654321',
            'email' => 'datletrong2306@gmail.com',
            'ref_id' => 'LETRONGDAT',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create Commission Policy for Lê Trọng Đạt
        CommissionPolicy::create([
            'collaborator_id' => $collaboratorId,
            'program_type' => ['regular', 'part_time', 'distance'],
            'role' => 'primary',
            'type' => 'fixed',
            'payout_rules' => [
                'regular' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 1750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng tuyển sinh chính quy']
                ],
                'part_time' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng đợt 1 (Xác nhận phí)'],
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 1450000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng đợt 2 (Nhập học)']
                ],
                'distance' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng đợt 1 (Xác nhận phí)'],
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 1450000, 'payout_trigger' => 'student_enrolled', 'description' => 'Hoa hồng đợt 2 (Nhập học)']
                ]
            ],
            'trigger' => 'on_verification',
            'visibility' => 'internal',
            'priority' => 10,
            'active' => true,
        ]);

        $programs = [
            'REGULAR' => [
                'name' => 'CNTT - CQ',
                'fee' => 1750000,
                'expected_immediate_commission' => 1750000.0,
                'expected_pending_commission' => 0.0,
            ],
            'PART_TIME' => [
                'name' => 'CNTT - VHVL',
                'fee' => 750000,
                'expected_immediate_commission' => 750000.0,
                'expected_pending_commission' => 1450000.0,
            ],
            'DISTANCE' => [
                'name' => 'CNTT - TX',
                'fee' => 750000,
                'expected_immediate_commission' => 750000.0,
                'expected_pending_commission' => 1450000.0,
            ],
        ];

        foreach ($programs as $progType => $config) {
            // Clean up wallets for clean wallet calculations
            Wallet::query()->delete();
            WalletTransaction::query()->delete();

            // Create Quota
            $quotaId = DB::table('quotas')->insertGetId([
                'intake_id' => $intakeId,
                'name' => $config['name'],
                'major_name' => 'Công nghệ thông tin',
                'program_name' => $progType,
                'target_quota' => 10,
                'current_quota' => 0,
                'pending_quota' => 0,
                'reserved_quota' => 0,
                'tuition_fee' => $config['fee'],
                'status' => 'active',
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            // Create Student
            $student = Student::factory()->create([
                'collaborator_id' => $collaboratorId,
                'quota_id' => $quotaId,
                'intake_id' => $intakeId,
                'program_type' => $progType,
                'major' => 'Công nghệ thông tin',
            ]);

            // Create Payment
            $payment = Payment::factory()->create([
                'student_id' => $student->id,
                'primary_collaborator_id' => $collaboratorId,
                'program_type' => $progType,
                'amount' => $config['fee'],
                'status' => 'submitted',
            ]);

            // Verify Payment
            $payment->update(['status' => 'verified']);

            // Verify Quota incremented
            $this->assertEquals(1, DB::table('quotas')->where('id', $quotaId)->value('current_quota'));

            // Verify Commission and its Items
            $commission = $payment->fresh()->commission;
            $this->assertNotNull($commission);

            // Verify immediate commission item
            $immediateItem = $commission->items()
                ->where('role', 'direct')
                ->where('trigger', 'payment_verified')
                ->first();
            $this->assertNotNull($immediateItem);
            $this->assertEquals($config['expected_immediate_commission'], (float)$immediateItem->amount);
            $this->assertEquals('payable', $immediateItem->status);

            // Verify pending commission item (if any)
            if ($config['expected_pending_commission'] > 0) {
                $pendingItem = $commission->items()
                    ->where('role', 'direct')
                    ->where('trigger', 'student_enrolled')
                    ->first();
                $this->assertNotNull($pendingItem);
                $this->assertEquals($config['expected_pending_commission'], (float)$pendingItem->amount);
                $this->assertEquals('pending', $pendingItem->status);
            }

            // Simulate Accountant pays and Collaborator confirms receipt of immediate item
            $immediateItem->markAsPaymentConfirmed(null, 1);
            $commissionService->confirmDirectReceived($immediateItem, 1);

            // Check wallet balance reflects only the immediate amount
            $wallet = Wallet::where('collaborator_id', $collaboratorId)->first();
            $this->assertNotNull($wallet);
            $this->assertEquals($config['expected_immediate_commission'], (float)$wallet->balance);

            // Now, simulate student enrollment success
            $student->update(['status' => 'enrolled']);

            // Verify that pending items are unlocked to payable
            if ($config['expected_pending_commission'] > 0) {
                $pendingItem->refresh();
                $this->assertEquals('payable', $pendingItem->status);

                // Accountant pays and Collaborator confirms receipt of this unlocked item
                $pendingItem->markAsPaymentConfirmed(null, 1);
                $commissionService->confirmDirectReceived($pendingItem, 1);

                // Wallet should reflect total amount
                $wallet->refresh();
                $this->assertEquals(
                    $config['expected_immediate_commission'] + $config['expected_pending_commission'],
                    (float)$wallet->balance
                );
            }
        }
    }

    /**
     * Scenario: Student Dropout and Refund
     * - Student registers Regular (CQ), commission paid (1.75M, wallet = 1.75M).
     * - Student drops out (status -> dropped). Quota is released automatically.
     * - Payment is cancelled/reverted.
     * - Accountant manually creates a negative CommissionAdjustment to reclaim the commission.
     * - Wallet balance returns to 0.
     */
    public function test_scenario_student_dropout_and_refund() {
        $commissionService = new CommissionService();

        // 1. Create intake for June 2026
        $intakeId = DB::table('intakes')->insertGetId([
            'name' => 'Đợt Tháng 6/2026',
            'description' => 'Tuyển sinh tháng 6/2026',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'enrollment_deadline' => '2026-06-30',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 2. Create Quota
        $quotaId = DB::table('quotas')->insertGetId([
            'intake_id' => $intakeId,
            'name' => 'CNTT - CQ',
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'REGULAR',
            'target_quota' => 10,
            'current_quota' => 0,
            'pending_quota' => 0,
            'reserved_quota' => 0,
            'tuition_fee' => 1750000,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 3. Create collaborator Lê Trọng Đạt
        $collaboratorId = DB::table('collaborators')->insertGetId([
            'full_name' => 'Lê Trọng Đạt',
            'phone' => '0987654321',
            'email' => 'datletrong2306@gmail.com',
            'ref_id' => 'LETRONGDAT',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Create Commission Policy for Lê Trọng Đạt
        CommissionPolicy::create([
            'collaborator_id' => $collaboratorId,
            'program_type' => ['regular'],
            'role' => 'primary',
            'type' => 'fixed',
            'payout_rules' => [
                'regular' => [
                    ['recipient_type' => 'direct_ctv', 'amount_vnd' => 1750000, 'payout_trigger' => 'payment_verified', 'description' => 'Hoa hồng tuyển sinh chính quy']
                ]
            ],
            'trigger' => 'on_verification',
            'visibility' => 'internal',
            'priority' => 10,
            'active' => true,
        ]);

        // 4. Create student (REGULAR)
        $student = Student::factory()->create([
            'collaborator_id' => $collaboratorId,
            'quota_id' => $quotaId,
            'intake_id' => $intakeId,
            'program_type' => 'REGULAR',
            'major' => 'Công nghệ thông tin',
        ]);

        // 5. Create payment and verify
        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'primary_collaborator_id' => $collaboratorId,
            'program_type' => 'REGULAR',
            'amount' => 1750000,
            'status' => 'submitted',
        ]);
        $payment->update(['status' => 'verified']);

        // Check Quota consumed
        $this->assertEquals(1, DB::table('quotas')->where('id', $quotaId)->value('current_quota'));

        // Confirm paid to Đạt
        $commission = $payment->fresh()->commission;
        $item = $commission->items()->where('role', 'direct')->first();
        $item->markAsPaymentConfirmed(null, 1);
        $commissionService->confirmDirectReceived($item, 1);

        $wallet = Wallet::where('collaborator_id', $collaboratorId)->first();
        $this->assertEquals(1750000, (float)$wallet->balance);

        // 6. Student drops out (STATUS_DROPPED)
        $student->update(['status' => Student::STATUS_DROPPED]);

        // Check Quota is released automatically
        $this->assertEquals(0, DB::table('quotas')->where('id', $quotaId)->value('current_quota'));

        // 7. Payment is cancelled/reverted
        $payment->update(['status' => 'reverted']);

        // 8. Accountant manually creates a negative adjustment to reclaim the 1.75M commission
        $adjustment = CommissionAdjustment::create([
            'commission_id' => $commission->id,
            'recipient_collaborator_id' => $collaboratorId,
            'amount' => -1750000,
            'reason' => 'Thu hồi hoa hồng do sinh viên rút hồ sơ',
            'status' => 'received_confirmed',
            'created_by' => 1,
        ]);

        // Reclaim in wallet
        $commissionService->addCommissionToWallet(
            Collaborator::find($collaboratorId),
            -1750000,
            'Thu hồi hoa hồng do sinh viên rút hồ sơ',
            null,
            $adjustment->id
        );

        // Wallet balance returns to 0
        $wallet->refresh();
        $this->assertEquals(0, (float)$wallet->balance);
    }

    /**
     * Scenario: Lead Lock-Time (under 14 days)
     * - Collaborator A registers student.
     * - Collaborator B attempts to submit payment within 14 days.
     * - The request is blocked, collaborator remains A.
     */
    public function test_scenario_lead_lock_time_under_14_days_blocked() {
        $intakeId = DB::table('intakes')->insertGetId([
            'name' => 'Đợt Tháng 6/2026',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'enrollment_deadline' => '2026-06-30',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotaId = DB::table('quotas')->insertGetId([
            'intake_id' => $intakeId,
            'name' => 'CNTT - CQ',
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'REGULAR',
            'target_quota' => 10,
            'current_quota' => 0,
            'pending_quota' => 0,
            'reserved_quota' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $collaboratorAId = DB::table('collaborators')->insertGetId([
            'full_name' => 'CTV A',
            'phone' => '0912345678',
            'email' => 'ctv.a@example.com',
            'ref_id' => 'CTVAREF',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $collaboratorBId = DB::table('collaborators')->insertGetId([
            'full_name' => 'CTV B',
            'phone' => '0987654321',
            'email' => 'ctv.b@example.com',
            'ref_id' => 'CTVBREF',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student = Student::factory()->create([
            'phone' => '0868266410',
            'collaborator_id' => $collaboratorAId,
            'quota_id' => $quotaId,
            'intake_id' => $intakeId,
            'program_type' => 'REGULAR',
            'major' => 'Công nghệ thông tin',
            'created_at' => now(),
        ]);

        // Collaborator B submits payment
        $file = \Illuminate\Http\UploadedFile::fake()->create('bill.jpg', 500);

        $response = $this->post(route('public.ref.payment.submit', ['ref_id' => 'CTVBREF']), [
            'phone' => '0868266410',
            'amount' => 1750000,
            'program_type' => 'regular',
            'bill' => $file,
        ]);

        $response->assertSessionHasErrors('phone');
        $student->refresh();
        $this->assertEquals($collaboratorAId, $student->collaborator_id);
    }

    /**
     * Scenario: Lead Lock-Time (after 14 days)
     * - Collaborator A registers student.
     * - Student is unregistered for 15 days (unpaid).
     * - Collaborator B submits payment.
     * - Student collaborator is automatically transferred to B.
     */
    public function test_scenario_lead_lock_time_after_14_days_transferred() {
        $intakeId = DB::table('intakes')->insertGetId([
            'name' => 'Đợt Tháng 6/2026',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'enrollment_deadline' => '2026-06-30',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotaId = DB::table('quotas')->insertGetId([
            'intake_id' => $intakeId,
            'name' => 'CNTT - CQ',
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'REGULAR',
            'target_quota' => 10,
            'current_quota' => 0,
            'pending_quota' => 0,
            'reserved_quota' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $collaboratorAId = DB::table('collaborators')->insertGetId([
            'full_name' => 'CTV A',
            'phone' => '0912345678',
            'email' => 'ctv.a@example.com',
            'ref_id' => 'CTVAREF',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $collaboratorBId = DB::table('collaborators')->insertGetId([
            'full_name' => 'CTV B',
            'phone' => '0987654321',
            'email' => 'ctv.b@example.com',
            'ref_id' => 'CTVBREF',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student = Student::factory()->create([
            'phone' => '0868266410',
            'collaborator_id' => $collaboratorAId,
            'quota_id' => $quotaId,
            'intake_id' => $intakeId,
            'program_type' => 'REGULAR',
            'major' => 'Công nghệ thông tin',
            'created_at' => now()->subDays(15),
        ]);

        // Collaborator B submits payment
        $file = \Illuminate\Http\UploadedFile::fake()->create('bill.jpg', 500);

        $response = $this->post(route('public.ref.payment.submit', ['ref_id' => 'CTVBREF']), [
            'phone' => '0868266410',
            'amount' => 1750000,
            'program_type' => 'regular',
            'bill' => $file,
        ]);

        $response->assertSessionHasNoErrors();
        $student->refresh();
        $this->assertEquals($collaboratorBId, $student->collaborator_id);
    }

    /**
     * Scenario: Student Restoration reoccupies Quota
     * - Student is approved, payment verified (current_quota = 1).
     * - Student drops out (current_quota = 0).
     * - Student status changes back to enrolled (current_quota = 1).
     */
    public function test_scenario_student_restoration_reoccupies_quota() {
        $intakeId = DB::table('intakes')->insertGetId([
            'name' => 'Đợt Tháng 6/2026',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'enrollment_deadline' => '2026-06-30',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotaId = DB::table('quotas')->insertGetId([
            'intake_id' => $intakeId,
            'name' => 'CNTT - CQ',
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'REGULAR',
            'target_quota' => 10,
            'current_quota' => 0,
            'pending_quota' => 0,
            'reserved_quota' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $collaboratorId = DB::table('collaborators')->insertGetId([
            'full_name' => 'Lê Trọng Đạt',
            'phone' => '0987654321',
            'email' => 'datletrong2306@gmail.com',
            'ref_id' => 'LETRONGDAT',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student = Student::factory()->create([
            'collaborator_id' => $collaboratorId,
            'quota_id' => $quotaId,
            'intake_id' => $intakeId,
            'program_type' => 'REGULAR',
            'major' => 'Công nghệ thông tin',
            'created_at' => now(),
        ]);

        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'primary_collaborator_id' => $collaboratorId,
            'program_type' => 'REGULAR',
            'amount' => 1750000,
            'status' => 'submitted',
        ]);
        $payment->update(['status' => 'verified']);

        // Check Quota is consumed
        $this->assertEquals(1, DB::table('quotas')->where('id', $quotaId)->value('current_quota'));

        // Student drops out -> quota released
        $student->update(['status' => Student::STATUS_DROPPED]);
        $this->assertEquals(0, DB::table('quotas')->where('id', $quotaId)->value('current_quota'));

        // Student restored to enrolled -> quota reoccupied!
        $student->update(['status' => Student::STATUS_ENROLLED]);
        $this->assertEquals(1, DB::table('quotas')->where('id', $quotaId)->value('current_quota'));
    }

    /**
     * Scenario: Unpaid Cancellation and Transfer
     * - Student registers, pending_quota = 1.
     * - Student drops out before payment -> pending_quota = 0.
     * - Student is restored to new -> pending_quota = 1.
     * - Student transfers to another program before payment -> old pending_quota = 0, new pending_quota = 1.
     */
    public function test_scenario_student_unpaid_cancellation_and_transfer() {
        $intakeId = DB::table('intakes')->insertGetId([
            'name' => 'Đợt Tháng 6/2026',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'enrollment_deadline' => '2026-06-30',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotaId1 = DB::table('quotas')->insertGetId([
            'intake_id' => $intakeId,
            'name' => 'CNTT - CQ',
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'REGULAR',
            'target_quota' => 10,
            'current_quota' => 0,
            'pending_quota' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotaId2 = DB::table('quotas')->insertGetId([
            'intake_id' => $intakeId,
            'name' => 'CNTT - VHVL',
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'PART_TIME',
            'target_quota' => 10,
            'current_quota' => 0,
            'pending_quota' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $collaboratorId = DB::table('collaborators')->insertGetId([
            'full_name' => 'Lê Trọng Đạt',
            'phone' => '0987654321',
            'email' => 'datletrong2306@gmail.com',
            'ref_id' => 'LETRONGDAT',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // 1. Student registers, pending_quota = 1
        $student = Student::factory()->create([
            'collaborator_id' => $collaboratorId,
            'quota_id' => $quotaId1,
            'intake_id' => $intakeId,
            'program_type' => 'REGULAR',
            'major' => 'Công nghệ thông tin',
            'status' => 'new',
        ]);
        (new \App\Services\QuotaService())->handleStudentRegistration($student);
        $this->assertEquals(1, DB::table('quotas')->where('id', $quotaId1)->value('pending_quota'));

        // 2. Student drops out before payment -> pending_quota = 0
        $student->update(['status' => Student::STATUS_DROPPED]);
        $this->assertEquals(0, DB::table('quotas')->where('id', $quotaId1)->value('pending_quota'));

        // 3. Student restored to new -> pending_quota = 1
        $student->update(['status' => Student::STATUS_NEW]);
        $this->assertEquals(1, DB::table('quotas')->where('id', $quotaId1)->value('pending_quota'));

        // 4. Student transfers before payment -> old pending_quota = 0, new pending_quota = 1
        $student->update(['quota_id' => $quotaId2, 'program_type' => 'PART_TIME']);
        $this->assertEquals(0, DB::table('quotas')->where('id', $quotaId1)->value('pending_quota'));
        $this->assertEquals(1, DB::table('quotas')->where('id', $quotaId2)->value('pending_quota'));
    }

    /**
     * Scenario: Walk-in Student registration
     * - Student registered with source = 'walkin'.
     * - collaborator_id is automatically set to null.
     * - Payment is verified, but NO commission is created.
     */
    public function test_scenario_student_walkin_no_collaborator() {
        $intakeId = DB::table('intakes')->insertGetId([
            'name' => 'Đợt Tháng 6/2026',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-30',
            'enrollment_deadline' => '2026-06-30',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $quotaId = DB::table('quotas')->insertGetId([
            'intake_id' => $intakeId,
            'name' => 'CNTT - CQ',
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'REGULAR',
            'target_quota' => 10,
            'current_quota' => 0,
            'pending_quota' => 0,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $collaboratorId = DB::table('collaborators')->insertGetId([
            'full_name' => 'Lê Trọng Đạt',
            'phone' => '0987654321',
            'email' => 'datletrong2306@gmail.com',
            'ref_id' => 'LETRONGDAT',
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Student created with source = 'walkin' and collaborator_id assigned originally
        $student = Student::factory()->create([
            'collaborator_id' => $collaboratorId,
            'quota_id' => $quotaId,
            'intake_id' => $intakeId,
            'program_type' => 'REGULAR',
            'major' => 'Công nghệ thông tin',
            'source' => 'walkin',
        ]);

        // collaborator_id is automatically set to null by StudentObserver
        $this->assertNull($student->collaborator_id);

        // Payment verified
        $payment = Payment::factory()->create([
            'student_id' => $student->id,
            'primary_collaborator_id' => null,
            'program_type' => 'REGULAR',
            'amount' => 1750000,
            'status' => 'submitted',
        ]);
        $payment->update(['status' => 'verified']);

        // Assert NO commission record is created since there is no collaborator
        $this->assertNull($payment->fresh()->commission);
    }
}

