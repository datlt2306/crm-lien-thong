<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use App\Services\QuotaService;

class QuotaServiceTest extends TestCase {
    use RefreshDatabase;

    protected QuotaService $quotaService;

    protected function setUp(): void {
        parent::setUp();
        $this->quotaService = new QuotaService();
    }

    private function createIntake(): int {
        return (int) DB::table('intakes')->insertGetId([
            'name' => 'Đợt 1',
            'description' => 'Test intake',
            'start_date' => '2026-01-01',
            'end_date' => '2026-12-31',
            'enrollment_deadline' => '2026-12-31',
            'status' => 'active',
            'organization_id' => 1,
            'settings' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createQuota(int $intakeId, int $target, int $current): int {
        return (int) DB::table('quotas')->insertGetId([
            'intake_id' => $intakeId,
            'name' => 'CNTT - Chính quy',
            'organization_id' => 1,
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'REGULAR',
            'target_quota' => $target,
            'current_quota' => $current,
            'pending_quota' => 0,
            'reserved_quota' => 0,
            'tuition_fee' => null,
            'notes' => null,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function createCollaborator(): int {
        return (int) DB::table('collaborators')->insertGetId([
            'full_name' => 'CTV Test',
            'phone' => '09' . fake()->unique()->numerify('########'),
            'email' => fake()->unique()->safeEmail(),
            'organization_id' => 1,
            'ref_id' => strtoupper(fake()->unique()->lexify('????????')),
            'note' => null,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_consume_quota_on_payment_verified_updates_quota_and_annual_quota() {
        $intakeId = $this->createIntake();
        $quotaId = $this->createQuota($intakeId, 10, 2);

        DB::table('annual_quotas')->insert([
            'name' => 'AQ 2026',
            'organization_id' => 1,
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'REGULAR',
            'year' => 2026,
            'target_quota' => 50,
            'current_quota' => 10,
            'status' => 'active',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $collaboratorId = $this->createCollaborator();

        $student = Student::factory()->create([
            'organization_id' => 1,
            'collaborator_id' => $collaboratorId,
            'quota_id' => $quotaId,
            'intake_id' => $intakeId,
            'program_type' => 'REGULAR',
            'major' => 'Công nghệ thông tin',
        ]);

        $payment = Payment::factory()->create([
            'organization_id' => 1,
            'student_id' => $student->id,
            'primary_collaborator_id' => $collaboratorId,
            'status' => 'submitted',
        ]);

        $result = $this->quotaService->consumeQuotaOnPaymentVerified($payment);
        $this->assertTrue($result);

        $this->assertEquals(3, DB::table('quotas')->where('id', $quotaId)->value('current_quota'));
        $this->assertEquals(11, DB::table('annual_quotas')->value('current_quota'));
    }

    public function test_decrease_quota_on_payment_submission_alias_works() {
        $intakeId = $this->createIntake();
        $quotaId = $this->createQuota($intakeId, 5, 1);

        $collaboratorId = $this->createCollaborator();

        $student = Student::factory()->create([
            'organization_id' => 1,
            'collaborator_id' => $collaboratorId,
            'quota_id' => $quotaId,
            'intake_id' => $intakeId,
            'program_type' => 'REGULAR',
            'major' => 'Công nghệ thông tin',
        ]);

        $payment = Payment::factory()->create([
            'organization_id' => 1,
            'student_id' => $student->id,
            'primary_collaborator_id' => $collaboratorId,
            'status' => 'submitted',
        ]);

        $result = $this->quotaService->decreaseQuotaOnPaymentSubmission($payment);
        $this->assertTrue($result);
        $this->assertEquals(2, DB::table('quotas')->where('id', $quotaId)->value('current_quota'));
    }

    public function test_consume_quota_returns_false_when_quota_is_full() {
        $intakeId = $this->createIntake();
        $quotaId = $this->createQuota($intakeId, 2, 2);

        $collaboratorId = $this->createCollaborator();

        $student = Student::factory()->create([
            'organization_id' => 1,
            'collaborator_id' => $collaboratorId,
            'quota_id' => $quotaId,
            'intake_id' => $intakeId,
            'program_type' => 'REGULAR',
            'major' => 'Công nghệ thông tin',
        ]);

        $payment = Payment::factory()->create([
            'organization_id' => 1,
            'student_id' => $student->id,
            'primary_collaborator_id' => $collaboratorId,
            'status' => 'submitted',
        ]);

        $result = $this->quotaService->consumeQuotaOnPaymentVerified($payment);
        $this->assertFalse($result);
        $this->assertEquals(2, DB::table('quotas')->where('id', $quotaId)->value('current_quota'));
    }

    public function test_restore_quota_on_payment_reverted_decrements_current_quota() {
        $intakeId = $this->createIntake();
        $quotaId = $this->createQuota($intakeId, 10, 4);

        DB::table('annual_quotas')->insert([
            'name' => 'AQ 2026',
            'organization_id' => 1,
            'major_name' => 'Công nghệ thông tin',
            'program_name' => 'REGULAR',
            'year' => 2026,
            'target_quota' => 50,
            'current_quota' => 20,
            'status' => 'active',
            'notes' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $collaboratorId = $this->createCollaborator();

        $student = Student::factory()->create([
            'organization_id' => 1,
            'collaborator_id' => $collaboratorId,
            'quota_id' => $quotaId,
            'intake_id' => $intakeId,
            'program_type' => 'REGULAR',
            'major' => 'Công nghệ thông tin',
        ]);

        // Sử dụng saveQuietly() để bỏ qua observer tạo mới nhằm giữ nguyên current_quota = 4 của test
        $payment = Payment::factory()->make([
            'organization_id' => 1,
            'student_id' => $student->id,
            'primary_collaborator_id' => $collaboratorId,
            'status' => 'verified',
        ]);
        $payment->saveQuietly();

        $result = $this->quotaService->restoreQuotaOnPaymentReverted($payment);
        $this->assertTrue($result);

        $this->assertEquals(3, DB::table('quotas')->where('id', $quotaId)->value('current_quota'));
        $this->assertEquals(19, DB::table('annual_quotas')->value('current_quota'));
    }
}
