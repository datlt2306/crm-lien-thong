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
                'email' => 'datletrong2306@gmail.com',
                'note' => 'Admin - CTV chính',
                'status' => 'active',
            ],
            [
                'full_name' => 'Tạ Hải Long',
                'phone' => '0987654322',
                'email' => 'tahailongseo@gmail.com',
                'note' => 'CTV Marketing',
                'status' => 'active',
            ],
            [
                'full_name' => 'Đặng Tiến Sơn',
                'phone' => '0987654323',
                'email' => 'sondt32@fpt.edu.vn',
                'note' => 'CTV Admissions',
                'status' => 'active',
            ],
        ];

        foreach ($collaborators as $collaboratorData) {
            // Tạo hoặc cập nhật User account cho collaborator
            $user = User::firstOrCreate(
                ['email' => $collaboratorData['email']],
                [
                    'name' => $collaboratorData['full_name'],
                    'password' => bcrypt('123456'), // Mật khẩu mặc định chỉ dùng nếu tạo mới
                    'role' => 'ctv',
                ]
            );

            // Đảm bảo role 'ctv' được gán
            if (!$user->hasRole('ctv')) {
                $user->assignRole('ctv');
            }

            // Tạo hoặc cập nhật Collaborator record
            Collaborator::updateOrCreate(
                ['email' => $collaboratorData['email']],
                $collaboratorData
            );
        }
    }
}
