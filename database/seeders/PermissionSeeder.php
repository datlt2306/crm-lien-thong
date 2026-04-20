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
            // Student Management
            'student_view_any',
            'student_view',
            'student_create',
            'student_update',
            'student_delete',
            'student_restore',
            'student_force_delete',
            'student_export',
            'student_import',
            'student_change_status',
            'student_assign',
            'student_view_history',

            // Financial Management
            'payment_view_any',
            'payment_view',
            'payment_create',
            'payment_update',
            'payment_delete',
            'payment_verify',
            'payment_reverse',
            'payment_upload_receipt',
            'payment_report',
            'payment_view_all', // Xem tất cả thay vì chỉ của mình

            // Commission Management
            'commission_view_any',
            'commission_view',
            'commission_calculate',
            'commission_update',
            'commission_payout',
            'commission_view_team',

            // User & Personnel
            'user_view_any',
            'user_create',
            'user_update',
            'user_delete',
            'user_manage_roles',

            // Collaborator (CTV)
            'collaborator_view_any',
            'collaborator_create',
            'collaborator_update',
            'collaborator_delete',
            'collaborator_verify',
            'collaborator_assign_quota',

            // System & Tools
            'audit_log_view',
            'role_manage',
            'setting_manage',
            'database_backup',
            'report_view_all',
            'report_view_finance',
            'report_view_enrollment',
        ];

        foreach ($permissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }
        Permission::whereNotIn('name', $permissions)->delete();

        // Tạo roles nếu chưa có
        $superAdmin = Role::firstOrCreate(['name' => 'super_admin']);
        $ctv = Role::firstOrCreate(['name' => 'ctv']);
        $accountant = Role::firstOrCreate(['name' => 'accountant']);
        $admissions = Role::firstOrCreate(['name' => 'admissions']);
        $document = Role::firstOrCreate(['name' => 'document']);

        // Gán permissions cho super_admin (tất cả permissions)
        $superAdmin->syncPermissions(Permission::all());


        // Gán permissions cho CTV
        $ctv->syncPermissions([
            'commission_view_any',
            'payment_view_any',
            'payment_upload_receipt',
            'student_view_any',
            'student_create',
            'student_update',
        ]);

        // Gán permissions cho accountant
        $accountant->syncPermissions([
            'student_view_any',
            'student_view',
            'student_update',
            'commission_view_any',
            'payment_view_any',
            'payment_upload_receipt',
            'payment_verify',
            'report_view_finance',
        ]);

        // Gán permissions cho admissions
        $admissions->syncPermissions([
            'student_view_any',
            'student_create',
            'student_update',
            'collaborator_view_any',
            'report_view_enrollment',
        ]);

        // Gán permissions cho document
        $document->syncPermissions([
            'student_view_any',
            'student_update',
            'payment_view_any',
            'payment_upload_receipt',
        ]);

        $this->command->info('Permissions and roles created successfully!');
    }
}
