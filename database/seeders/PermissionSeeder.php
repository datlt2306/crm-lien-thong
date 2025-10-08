<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class PermissionSeeder extends Seeder {
    /**
     * Run the database seeds.
     */
    public function run(): void {
        // Tạo permissions
        $permissions = [
            'manage_commission',
            'view_commission',
            'manage_payment',
            'view_payment',
            'manage_student',
            'view_student',
            'manage_collaborator',
            'view_collaborator',
            'manage_organization',
            'view_organization',
            'verify_payment',
            'manage_ctv',
            'manage_org',
            'upload_receipt',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // Tạo roles nếu chưa có
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $organizationOwner = Role::firstOrCreate(['name' => 'organization_owner']);
        $ctv = Role::firstOrCreate(['name' => 'ctv']);
        $accountant = Role::firstOrCreate(['name' => 'accountant']);
        $admissions = Role::firstOrCreate(['name' => 'admissions']);
        $document = Role::firstOrCreate(['name' => 'document']);

        // Gán permissions cho super_admin (tất cả permissions)
        $superAdmin->syncPermissions(Permission::all());

        // Gán permissions cho organization_owner
        $organizationOwner->syncPermissions([
            'view_commission',
            'manage_commission',
            'verify_payment',
            'manage_payment',
            'view_payment',
            'view_student',
            'view_collaborator',
            'view_organization',
            'manage_organization'
        ]);

        // Gán permissions cho CTV
        $ctv->syncPermissions([
            'view_commission',
            'view_payment',
            'view_student'
        ]);

        // Gán permissions cho accountant
        $accountant->syncPermissions([
            'view_commission',
            'view_payment',
            'upload_receipt',
            'verify_payment',
        ]);

        // Gán permissions cho admissions
        $admissions->syncPermissions([
            'view_student',
            'manage_student',
            'view_collaborator',
            'view_payment',
        ]);

        // Gán permissions cho document
        $document->syncPermissions([
            'view_student',
            'manage_student',
            'view_payment',
            'upload_receipt',
        ]);

        $this->command->info('Permissions and roles created successfully!');
    }
}
