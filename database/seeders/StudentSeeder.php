<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\Collaborator;
use App\Models\Quota;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder {
    public function run(): void {

        $collaborators = Collaborator::where('status', 'active')->get();
        if ($collaborators->isEmpty()) {
            $this->command->error('Chưa có Collaborator active nào. Chạy CollaboratorSeeder trước.');
            return;
        }

        $quotas = Quota::with('intake')->get();
        if ($quotas->isEmpty()) {
            $this->command->error('Chưa có quota nào. Chạy IntakeQuotaSeeder trước.');
            return;
        }

        $students = [
            [
                'full_name' => 'Nguyễn Thị Anh',
                'phone' => '0123456789',
                'email' => 'anh.nguyen@example.com',
                
                'collaborator_id' => $collaborators->random()->id,

                'target_university' => 'University of Melbourne',
                'major' => 'Computer Science',
                'source' => 'ref',
                'status' => 'new',
                'dob' => '2000-05-15',
                'address' => 'Hà Nội',
                'notes' => 'Học sinh giỏi, có IELTS 7.0',
            ],
            [
                'full_name' => 'Trần Văn Bình',
                'phone' => '0123456790',
                'email' => 'binh.tran@example.com',
                
                'collaborator_id' => $collaborators->random()->id,

                'target_university' => 'University of Sydney',
                'major' => 'Business Administration',
                'source' => 'ref',
                'status' => 'submitted',
                'dob' => '1999-08-20',
                'address' => 'TP.HCM',
                'notes' => 'Có kinh nghiệm làm việc 2 năm',
            ],
            [
                'full_name' => 'Lê Thị Cẩm',
                'phone' => '0123456791',
                'email' => 'cam.le@example.com',
                
                'collaborator_id' => $collaborators->random()->id,

                'target_university' => 'Monash University',
                'major' => 'International Business',
                'source' => 'form',
                'status' => 'approved',
                'dob' => '2001-03-10',
                'address' => 'Đà Nẵng',
                'notes' => 'IELTS 7.5, GPA 3.8',
            ],
            [
                'full_name' => 'Phạm Văn Dũng',
                'phone' => '0123456792',
                'email' => 'dung.pham@example.com',
                
                'collaborator_id' => $collaborators->random()->id,

                'target_university' => 'RMIT University',
                'major' => 'Information Technology',
                'source' => 'ref',
                'status' => 'enrolled',
                'dob' => '2000-12-25',
                'address' => 'Hà Nội',
                'notes' => 'Đã nhập học tháng 9/2026',
            ],
            [
                'full_name' => 'Hoàng Thị Em',
                'phone' => '0123456793',
                'email' => 'em.hoang@example.com',
                
                'collaborator_id' => $collaborators->random()->id,

                'target_university' => 'University of Queensland',
                'major' => 'Education',
                'source' => 'facebook',
                'status' => 'new',
                'dob' => '2001-07-08',
                'address' => 'Hải Phòng',
                'notes' => 'Quan tâm đến ngành giáo dục',
            ],
            [
                'full_name' => 'Vũ Văn Phúc',
                'phone' => '0123456794',
                'email' => 'phuc.vu@example.com',
                
                'collaborator_id' => $collaborators->random()->id,

                'target_university' => 'University of New South Wales',
                'major' => 'Engineering',
                'source' => 'ref',
                'status' => 'submitted',
                'dob' => '1999-11-30',
                'address' => 'TP.HCM',
                'notes' => 'Có background kỹ thuật tốt',
            ],
            [
                'full_name' => 'Đỗ Thị Giang',
                'phone' => '0123456795',
                'email' => 'giang.do@example.com',
                
                'collaborator_id' => $collaborators->random()->id,

                'target_university' => 'University of Western Australia',
                'major' => 'Medicine',
                'source' => 'form',
                'status' => 'approved',
                'dob' => '2000-04-12',
                'address' => 'Hà Nội',
                'notes' => 'Học sinh xuất sắc, có chứng chỉ y tế',
            ],
            [
                'full_name' => 'Nguyễn Văn Hùng',
                'phone' => '0123456796',
                'email' => 'hung.nguyen@example.com',
                
                'collaborator_id' => $collaborators->random()->id,

                'target_university' => 'Australian National University',
                'major' => 'Law',
                'source' => 'ref',
                'status' => 'rejected',
                'dob' => '1998-09-18',
                'address' => 'TP.HCM',
                'notes' => 'Hồ sơ bị từ chối do thiếu chứng chỉ',
            ],
            [
                'full_name' => 'Trần Thị Hương',
                'phone' => '0123456797',
                'email' => 'huong.tran@example.com',
                
                'collaborator_id' => $collaborators->random()->id,

                'target_university' => 'University of Adelaide',
                'major' => 'Architecture',
                'source' => 'facebook',
                'status' => 'new',
                'dob' => '2001-01-22',
                'address' => 'Đà Nẵng',
                'notes' => 'Có portfolio thiết kế đẹp',
            ],
            [
                'full_name' => 'Lê Văn Khoa',
                'phone' => '0123456798',
                'email' => 'khoa.le@example.com',
                
                'collaborator_id' => $collaborators->random()->id,

                'target_university' => 'Griffith University',
                'major' => 'Marketing',
                'source' => 'form',
                'status' => 'enrolled',
                'dob' => '2000-06-05',
                'address' => 'Hà Nội',
                'notes' => 'Đã nhập học tháng 7/2026',
            ],
        ];

        foreach ($students as $studentData) {
            $quota = $quotas->random();
            $studentData['quota_id'] = $quota->id;
            $studentData['intake_id'] = $quota->intake_id;
            
            // Gán ngẫu nhiên hệ đào tạo theo logic chuẩn mới
            $studentData['program_type'] = collect(['REGULAR', 'PART_TIME', 'DISTANCE'])->random();
            
            $student = Student::updateOrCreate(
                ['email' => $studentData['email']],
                $studentData
            );

            // Tạo dữ liệu lệ phí mẫu cho TẤT CẢ sinh viên để hiển thị trên UI
            $admissionFee = match($student->program_type) {
                'REGULAR' => 1750000,
                'PART_TIME' => 750000,
                'DISTANCE' => 200000,
                default => 750000,
            };

            \App\Models\Payment::updateOrCreate(
                ['student_id' => $student->id],
                [
                    'primary_collaborator_id' => $student->collaborator_id,
                    'program_type' => $student->program_type,
                    'amount' => $admissionFee,
                    'status' => in_array($student->status, [Student::STATUS_NEW, Student::STATUS_CONTACTED]) 
                        ? \App\Models\Payment::STATUS_SUBMITTED 
                        : \App\Models\Payment::STATUS_VERIFIED,
                    'verified_at' => in_array($student->status, [Student::STATUS_NEW, Student::STATUS_CONTACTED]) ? null : now(),
                    'verified_by' => in_array($student->status, [Student::STATUS_NEW, Student::STATUS_CONTACTED]) ? null : 1,
                ]
            );
        }
    }
}
