<?php

namespace Database\Seeders;

use App\Models\Collaborator;
use App\Models\User;
use Illuminate\Database\Seeder;

class CollaboratorSeeder extends Seeder {
    public function run(): void {

        $collaborators = [
            [
                'full_name' => 'Lê Trọng Đạt',
                'phone' => '0987654321',
                'email' => 'dat.le@example.com',
                
                'note' => 'CTV cấp cao, kinh nghiệm 3 năm',
                'status' => 'active',
            ],
            [
                'full_name' => 'Nguyễn Văn Kiên',
                'phone' => '0987654322',
                'email' => 'kien.nguyen@example.com',
                
                'note' => 'CTV chuyên về du học Úc',
                'status' => 'active',
            ],
            [
                'full_name' => 'Trần Thị Long',
                'phone' => '0987654323',
                'email' => 'long.tran@example.com',
                
                'note' => 'CTV mới, đang training',
                'status' => 'active',
            ],
            [
                'full_name' => 'Phạm Văn Minh',
                'phone' => '0987654324',
                'email' => 'minh.pham@example.com',
                
                'note' => 'CTV chuyên về du học Mỹ',
                'status' => 'inactive',
            ],
            [
                'full_name' => 'Hoàng Thị Lan',
                'phone' => '0987654325',
                'email' => 'lan.hoang@example.com',
                
                'note' => 'CTV chuyên về du học Canada',
                'status' => 'active',
            ],
        ];

        foreach ($collaborators as $collaboratorData) {
            // Tạo User account cho collaborator
            $user = User::create([
                'name' => $collaboratorData['full_name'],
                'email' => $collaboratorData['email'],
                'password' => bcrypt('123456'), // Mật khẩu mặc định
                'role' => 'ctv',
            ]);

            // Gán role 'ctv' cho collaborator
            $user->assignRole('ctv');

            // Tạo Collaborator record
            $collaborator = Collaborator::create($collaboratorData);

        }
    }
}
