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
            'name' => 'Super Admin',
            'email' => 'datlt2306@gmail.com',
            'password' => bcrypt('GZN6#EPERXl#kJkd'),
            'role' => 'super_admin',
        ]);

        // Seed permissions và roles trước
        $this->call(PermissionSeeder::class);

        // Gán role cho admin
        $superAdmin = User::where('email', 'datlt2306@gmail.com')->first();
        if ($superAdmin) {
            $superAdmin->assignRole('super_admin');
        }


        // Seed chương trình đào tạo
        $this->call(ProgramSeeder::class);

        // Majors
        $this->call(MajorSeeder::class);

        // Seed đợt tuyển sinh + chỉ tiêu trước khi tạo học sinh
        $this->call(IntakeQuotaSeeder::class);

        // Sau đó mới tạo collaborators và students
        $this->call(CollaboratorSeeder::class);
        $this->call(StudentSeeder::class);
        $this->call(CommissionPolicySeeder::class);
        $this->call(WalletSeeder::class);

        // Tạo dữ liệu test cho charts
        $this->call(ChartTestDataSeeder::class);

        // Tạo user kế toán
        $this->call(AccountantSeeder::class);
    }
}
