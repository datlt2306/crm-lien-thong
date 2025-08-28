<?php

namespace Database\Seeders;

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder {
    /**
     * Seed the application's database.
     */
    public function run(): void {
        // User::factory(10)->create();

        // Tạo admin user
        \App\Models\User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin@gmail.com'),
            'role' => 'super_admin',
        ]);

        // Seed permissions và roles trước
        $this->call(PermissionSeeder::class);

        // Gán role cho admin
        $superAdmin = User::where('email', 'admin@gmail.com')->first();
        if ($superAdmin) {
            $superAdmin->assignRole('super_admin');
        }

        $this->call(OrganizationSeeder::class);

        // Seed chương trình đào tạo
        $this->call(ProgramSeeder::class);

        // Majors
        $this->call(MajorSeeder::class);

        // Sau đó mới tạo collaborators và students
        $this->call(CollaboratorSeeder::class);
        $this->call(StudentSeeder::class);
        $this->call(CommissionPolicySeeder::class);
        $this->call(WalletSeeder::class);

        // Tạo dữ liệu test cho charts
        $this->call(ChartTestDataSeeder::class);
    }
}
