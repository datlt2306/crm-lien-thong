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

        \App\Models\User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@gmail.com',
            'password' => bcrypt('admin@gmail.com'),
            'role' => 'super_admin',
        ]);
        $this->call(OrganizationSeeder::class);

        // Seed roles & permissions trước
        $permissions = [
            'view_finance',
            'verify_payment',
            'manage_commission',
            'manage_ctv',
            'manage_org',
            'manage_student'
        ];
        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }
        $roles = [
            'super_admin',
            'org_admin',
            'senior_ctv',
            'ctv',
            'user'
        ];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role]);
        }
        $superAdmin = User::where('email', 'admin@gmail.com')->first();
        if ($superAdmin) {
            $superAdmin->assignRole('super_admin');
            $superAdmin->syncPermissions(Permission::all());
        }

        // Sau đó mới tạo collaborators và students
        $this->call(CollaboratorSeeder::class);
        $this->call(StudentSeeder::class);
        $this->call(CommissionPolicySeeder::class);
    }
}
