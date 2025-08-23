<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Services\QuotaService;
use App\Models\Student;
use App\Models\Payment;
use App\Models\Organization;
use App\Models\Major;
use App\Models\Collaborator;
use Illuminate\Support\Facades\DB;

class QuotaServiceTest extends TestCase {
    use RefreshDatabase;

    protected QuotaService $quotaService;

    protected function setUp(): void {
        parent::setUp();
        $this->quotaService = new QuotaService();
    }

    public function test_decrease_quota_on_student_registration() {
        // Tạo dữ liệu test
        $organization = Organization::factory()->create();
        $major = Major::factory()->create(['name' => 'Công nghệ thông tin']);

        // Tạo quota ban đầu = 10
        DB::table('major_organization')->insert([
            'organization_id' => $organization->id,
            'major_id' => $major->id,
            'quota' => 10,
            'intake_months' => json_encode([6, 9, 12]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Tạo student
        $student = Student::factory()->create([
            'organization_id' => $organization->id,
            'major' => 'Công nghệ thông tin',
        ]);

        // Giảm quota
        $result = $this->quotaService->decreaseQuotaOnStudentRegistration($student);

        // Kiểm tra kết quả
        $this->assertTrue($result);

        // Kiểm tra quota đã giảm
        $updatedQuota = DB::table('major_organization')
            ->where('organization_id', $organization->id)
            ->where('major_id', $major->id)
            ->value('quota');

        $this->assertEquals(9, $updatedQuota);
    }

    public function test_decrease_quota_on_payment_submission() {
        // Tạo dữ liệu test
        $organization = Organization::factory()->create();
        $major = Major::factory()->create(['name' => 'Quản trị kinh doanh']);
        $student = Student::factory()->create([
            'organization_id' => $organization->id,
            'major' => 'Quản trị kinh doanh',
        ]);

        // Tạo quota ban đầu = 5
        DB::table('major_organization')->insert([
            'organization_id' => $organization->id,
            'major_id' => $major->id,
            'quota' => 5,
            'intake_months' => json_encode([6, 9, 12]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Tạo collaborator
        $collaborator = \App\Models\Collaborator::factory()->create([
            'organization_id' => $organization->id,
        ]);

        // Tạo payment
        $payment = Payment::factory()->create([
            'organization_id' => $organization->id,
            'student_id' => $student->id,
            'primary_collaborator_id' => $collaborator->id,
        ]);

        // Giảm quota
        $result = $this->quotaService->decreaseQuotaOnPaymentSubmission($payment);

        // Kiểm tra kết quả
        $this->assertTrue($result);

        // Kiểm tra quota đã giảm
        $updatedQuota = DB::table('major_organization')
            ->where('organization_id', $organization->id)
            ->where('major_id', $major->id)
            ->value('quota');

        $this->assertEquals(4, $updatedQuota);
    }

    public function test_cannot_decrease_quota_when_zero() {
        // Tạo dữ liệu test với quota = 0
        $organization = Organization::factory()->create();
        $major = Major::factory()->create(['name' => 'Kế toán']);

        DB::table('major_organization')->insert([
            'organization_id' => $organization->id,
            'major_id' => $major->id,
            'quota' => 0,
            'intake_months' => json_encode([6, 9, 12]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $student = Student::factory()->create([
            'organization_id' => $organization->id,
            'major' => 'Kế toán',
        ]);

        // Thử giảm quota
        $result = $this->quotaService->decreaseQuotaOnStudentRegistration($student);

        // Kiểm tra kết quả
        $this->assertFalse($result);

        // Kiểm tra quota vẫn = 0
        $updatedQuota = DB::table('major_organization')
            ->where('organization_id', $organization->id)
            ->where('major_id', $major->id)
            ->value('quota');

        $this->assertEquals(0, $updatedQuota);
    }

    public function test_has_quota_method() {
        // Tạo dữ liệu test
        $organization = Organization::factory()->create();
        $major = Major::factory()->create();

        DB::table('major_organization')->insert([
            'organization_id' => $organization->id,
            'major_id' => $major->id,
            'quota' => 3,
            'intake_months' => json_encode([6, 9, 12]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Kiểm tra có quota
        $this->assertTrue($this->quotaService->hasQuota($organization->id, $major->id));

        // Cập nhật quota = 0
        DB::table('major_organization')
            ->where('organization_id', $organization->id)
            ->where('major_id', $major->id)
            ->update(['quota' => 0]);

        // Kiểm tra không có quota
        $this->assertFalse($this->quotaService->hasQuota($organization->id, $major->id));
    }

    public function test_get_current_quota_method() {
        // Tạo dữ liệu test
        $organization = Organization::factory()->create();
        $major = Major::factory()->create();

        DB::table('major_organization')->insert([
            'organization_id' => $organization->id,
            'major_id' => $major->id,
            'quota' => 7,
            'intake_months' => json_encode([6, 9, 12]),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Kiểm tra quota hiện tại
        $this->assertEquals(7, $this->quotaService->getCurrentQuota($organization->id, $major->id));

        // Kiểm tra trường hợp không tồn tại
        $this->assertEquals(0, $this->quotaService->getCurrentQuota(999, 999));
    }
}
