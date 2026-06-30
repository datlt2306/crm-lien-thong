<?php

namespace Tests\Feature;

use App\Imports\StudentsImport;
use App\Models\Collaborator;
use App\Models\Student;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Collection;
use Tests\TestCase;

class StudentImportTest extends TestCase {
    use RefreshDatabase;

    public function test_import_students_success() {
        // Create a collaborator
        $collaborator = Collaborator::create([
            'full_name' => 'Lê Trọng Đạt',
            'phone' => '0987654321',
            'email' => 'dat@gmail.com',
            'status' => 'active',
        ]);

        $rows = new Collection([
            [
                'ho_va_ten' => 'Nguyễn Văn A',
                'so_dien_thoai' => '0912345678',
                'email' => 'a@gmail.com',
                'he_dao_tao' => 'Chính quy',
                'nganh_hoc' => 'Công nghệ thông tin',
                'nguoi_gioi_thieu' => 'Lê Trọng Đạt',
                'truong_dang_ky' => 'Đại học Bách Khoa',
            ],
            [
                'ho_va_ten' => 'Trần Thị B',
                'so_dien_thoai' => '0923456789',
                'email' => 'b@gmail.com',
                'he_dao_tao' => 'Vừa học vừa làm',
                'nganh_hoc' => 'Quản trị kinh doanh',
                'nguoi_gioi_thieu' => '0987654321', // reference by phone
                'truong_dang_ky' => 'Đại học Kinh tế',
            ],
        ]);

        $import = new StudentsImport();
        $import->collection($rows);

        $this->assertCount(2, $import->successRows);
        $this->assertCount(0, $import->skippedRows);
        $this->assertCount(0, $import->validationErrors);

        // Verify Student 1
        $studentA = Student::where('phone', '0912345678')->first();
        $this->assertNotNull($studentA);
        $this->assertEquals('Nguyễn Văn A', $studentA->full_name);
        $this->assertEquals('regular', strtolower($studentA->program_type));
        $this->assertEquals($collaborator->id, $studentA->collaborator_id);

        // Verify Student 2
        $studentB = Student::where('phone', '0923456789')->first();
        $this->assertNotNull($studentB);
        $this->assertEquals('Trần Thị B', $studentB->full_name);
        $this->assertEquals('part_time', strtolower($studentB->program_type));
        $this->assertEquals($collaborator->id, $studentB->collaborator_id);
    }

    public function test_import_students_skip_duplicates() {
        // Create an existing student
        Student::create([
            'full_name' => 'Học viên cũ',
            'phone' => '0912345678',
            'program_type' => (\Illuminate\Support\Facades\DB::getDriverName() === 'sqlite') ? 'REGULAR' : 'regular',
            'major' => 'Công nghệ thông tin',
            'status' => 'new',
        ]);

        $rows = new Collection([
            [
                'ho_va_ten' => 'Học viên trùng SĐT',
                'so_dien_thoai' => '0912345678', // Duplicate
                'he_dao_tao' => 'Từ xa',
                'nganh_hoc' => 'Ngôn ngữ Anh',
            ],
            [
                'ho_va_ten' => 'Học viên mới',
                'so_dien_thoai' => '0987654321', // Unique
                'he_dao_tao' => 'Từ xa',
                'nganh_hoc' => 'Ngôn ngữ Anh',
            ],
        ]);

        $import = new StudentsImport();
        $import->collection($rows);

        $this->assertCount(1, $import->successRows);
        $this->assertCount(1, $import->skippedRows);
        $this->assertCount(0, $import->validationErrors);

        $this->assertEquals('Học viên mới', $import->successRows[0]['name']);
        $this->assertEquals('Học viên trùng SĐT', $import->skippedRows[0]['name']);
    }

    public function test_import_students_validation_errors() {
        $rows = new Collection([
            [
                'ho_va_ten' => '', // Missing name
                'so_dien_thoai' => '0912345678',
                'he_dao_tao' => 'Chính quy',
                'nganh_hoc' => 'Công nghệ thông tin',
            ],
            [
                'ho_va_ten' => 'Trần Văn C',
                'so_dien_thoai' => '0923456789',
                'he_dao_tao' => 'Hệ tàu vũ trụ', // Invalid education program
                'nganh_hoc' => 'Công nghệ thông tin',
            ],
            [
                'ho_va_ten' => 'Lê Văn D',
                'so_dien_thoai' => '0934567890',
                'he_dao_tao' => 'Chính quy',
                'nganh_hoc' => '', // Missing major
            ],
        ]);

        $import = new StudentsImport();
        $import->collection($rows);

        $this->assertCount(0, $import->successRows);
        $this->assertCount(3, $import->validationErrors);

        $this->assertStringContainsString('Thiếu Họ và tên', $import->validationErrors[0]);
        $this->assertStringContainsString('Hệ đào tạo không hợp lệ', $import->validationErrors[1]);
        $this->assertStringContainsString('Thiếu Ngành học', $import->validationErrors[2]);
    }
}
