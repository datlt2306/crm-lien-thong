<?php

namespace Database\Seeders;

use App\Models\Student;
use App\Models\Organization;
use App\Models\Collaborator;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder {
    public function run(): void {
        $organization = Organization::first();
        if (!$organization) {
            $this->command->error('Chưa có Organization nào. Chạy OrganizationSeeder trước.');
            return;
        }

        $collaborators = Collaborator::where('status', 'active')->get();
        if ($collaborators->isEmpty()) {
            $this->command->error('Chưa có Collaborator active nào. Chạy CollaboratorSeeder trước.');
            return;
        }

        $students = [
            [
                'full_name' => 'Nguyễn Thị Anh',
                'phone' => '0123456789',
                'email' => 'anh.nguyen@example.com',
                'organization_id' => $organization->id,
                'collaborator_id' => $collaborators->random()->id,
                'current_college' => 'ĐH Bách Khoa Hà Nội',
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
                'organization_id' => $organization->id,
                'collaborator_id' => $collaborators->random()->id,
                'current_college' => 'ĐH Kinh tế Quốc dân',
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
                'organization_id' => $organization->id,
                'collaborator_id' => $collaborators->random()->id,
                'current_college' => 'ĐH Ngoại thương',
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
                'organization_id' => $organization->id,
                'collaborator_id' => $collaborators->random()->id,
                'current_college' => 'ĐH FPT',
                'target_university' => 'RMIT University',
                'major' => 'Information Technology',
                'source' => 'ref',
                'status' => 'enrolled',
                'dob' => '2000-12-25',
                'address' => 'Hà Nội',
                'notes' => 'Đã nhập học tháng 9/2024',
            ],
            [
                'full_name' => 'Hoàng Thị Em',
                'phone' => '0123456793',
                'email' => 'em.hoang@example.com',
                'organization_id' => $organization->id,
                'collaborator_id' => $collaborators->random()->id,
                'current_college' => 'ĐH Sư phạm Hà Nội',
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
                'organization_id' => $organization->id,
                'collaborator_id' => $collaborators->random()->id,
                'current_college' => 'ĐH Công nghệ',
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
                'organization_id' => $organization->id,
                'collaborator_id' => $collaborators->random()->id,
                'current_college' => 'ĐH Y Hà Nội',
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
                'organization_id' => $organization->id,
                'collaborator_id' => $collaborators->random()->id,
                'current_college' => 'ĐH Luật Hà Nội',
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
                'organization_id' => $organization->id,
                'collaborator_id' => $collaborators->random()->id,
                'current_college' => 'ĐH Kiến trúc',
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
                'organization_id' => $organization->id,
                'collaborator_id' => $collaborators->random()->id,
                'current_college' => 'ĐH Thương mại',
                'target_university' => 'Griffith University',
                'major' => 'Marketing',
                'source' => 'form',
                'status' => 'enrolled',
                'dob' => '2000-06-05',
                'address' => 'Hà Nội',
                'notes' => 'Đã nhập học tháng 7/2024',
            ],
        ];

        foreach ($students as $studentData) {
            Student::create($studentData);
        }
    }
}
