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
        // Đảm bảo role 'kế toán' tồn tại
        $role = Role::firstOrCreate(['name' => 'kế toán']);

        // Tạo hoặc lấy user kế toán
        $user = User::firstOrCreate(
            ['email' => 'ketoan@gmail.com'],
            [
                'name' => 'Kế toán',
                'password' => bcrypt('ketoan@gmail.com'),
                // Cột enum role của users không có 'kế toán', dùng 'ctv' làm mặc định
                'role' => 'ctv',
            ]
        );

        // Gán role spatie 'kế toán'
        if (!$user->hasRole('kế toán')) {
            $user->assignRole($role);
        }
    }
}
