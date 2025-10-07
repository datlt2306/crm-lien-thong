<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class AccountantSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        // Đảm bảo role 'accountant' tồn tại
        $role = Role::firstOrCreate(['name' => 'accountant']);

        // Tạo hoặc lấy user kế toán
        $user = User::firstOrCreate(
            ['email' => 'ketoan@gmail.com'],
            [
                'name' => 'Kế toán',
                'password' => bcrypt('ketoan@gmail.com'),
                // Cột enum role của users không có 'accountant', dùng 'ctv' làm mặc định
                'role' => 'ctv',
            ]
        );

        // Gán role spatie 'accountant'
        if (!$user->hasRole('accountant')) {
            $user->assignRole($role);
        }
    }
}
