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
            'payment_view_bill',
            'payment_verify',
            'payment_reverse',
            'payment_refund',
            'payment_upload_bill',
            'payment_upload_receipt',
            'payment_update_receipt',
            'payment_report',

            // Commission Management
            'commission_view_any',
            'commission_view',
            'commission_calculate',
            'commission_update',
            'commission_payout',

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
            'audit_log_view_all',
            'role_manage',
            'setting_manage',
            'database_backup',
            'report_view_all',
            'report_view_finance',
            'report_view_enrollment',

            // Recruitment & Quotas
            'intake_view_any',
            'intake_create',
            'intake_update',
            'intake_delete',
            'annual_quota_view_any',
            'annual_quota_create',
            'annual_quota_update',
            'annual_quota_delete',
            'quota_view_any',
            'quota_create',
            'quota_update',
            'quota_delete',
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


        // Gán permissions cho CTV (Đúng 11 quyền yêu cầu)
        $ctv->syncPermissions([
            'student_view_any',      // 1. Xem danh sách sinh viên
            'student_view',          // 2. Xem chi tiết sinh viên
            'payment_view_any',      // 3. Xem danh sách phiếu thu
            'payment_view',          // 4. Xem file Phiếu thu
            'payment_view_bill',     // 5. Xem Bill
            'payment_upload_bill',   // 6. Tải Bill lên
            'commission_view_any',   // 7. Xem danh sách hoa hồng
            'commission_view',       // 8. Xem chi tiết hoa hồng
            'intake_view_any',       // 9. Xem danh sách đợt tuyển sinh
            'annual_quota_view_any', // 10. Xem danh sách chỉ tiêu năm
            'audit_log_view',        // 11. Xem nhật ký hoạt động học viên
        ]);

        // Gán permissions cho accountant (Đúng danh sách yêu cầu)
        $accountant->syncPermissions([
            'student_view_any',      // Xem danh sách sinh viên
            'student_view',          // Xem chi tiết sinh viên
            'payment_view_any',      // Xem danh sách phiếu thu
            'payment_view',          // Xem chi tiết/File phiếu thu
            'payment_view_bill',     // Xem Bill
            'payment_verify',        // Duyệt thanh toán
            'payment_reverse',       // Hủy xác nhận (chưa có phiếu thu)
            'payment_refund',        // Hoàn trả (đã có phiếu thu)
            'payment_upload_bill',   // Tải Bill lên
            'payment_upload_receipt', // Tải Phiếu thu lên
            'payment_update_receipt', // Sửa Phiếu thu
            'commission_view_any',   // Xem danh sách hoa hồng
            'commission_view',       // Xem chi tiết hoa hồng
            'intake_view_any',       // Xem danh sách đợt tuyển sinh
            'annual_quota_view_any', // Xem danh sách chỉ tiêu năm
            'quota_view_any',        // Xem danh sách chỉ tiêu chi tiết
            'audit_log_view_all',    // Xem nhật ký hoạt động học viên
            'report_view_finance',   // Báo cáo doanh thu
            'report_view_enrollment', // Báo cáo tuyển sinh
        ]);

        // Gán permissions cho admissions
        $admissions->syncPermissions([
            'student_view_any',
            'student_view',
            'student_create',
            'student_update',
            'collaborator_view_any',
            'report_view_enrollment',
            'intake_view_any',
            'intake_create',
            'intake_update',
            'annual_quota_view_any',
            'annual_quota_create',
            'annual_quota_update',
            'quota_view_any',
            'quota_create',
            'quota_update',
        ]);

        // Gán permissions cho document (Đúng danh sách yêu cầu)
        $document->syncPermissions([
            'student_view_any',      // Xem danh sách sinh viên
            'student_view',          // Xem chi tiết sinh viên
            'student_update',        // Chỉnh sửa sinh viên
            'payment_view_any',      // Xem danh sách phiếu thu
            'payment_view',          // Xem chi tiết/File phiếu thu
            'payment_view_bill',     // Xem Bill
            'payment_verify',        // Duyệt thanh toán
            'payment_reverse',       // Hủy xác nhận (chưa có phiếu thu)
            'payment_refund',        // Hoàn trả (đã có phiếu thu)
            'payment_upload_bill',   // Tải Bill lên
            'payment_upload_receipt', // Tải Phiếu thu lên
            'payment_update_receipt', // Sửa Phiếu thu
            'intake_view_any',       // Xem danh sách đợt tuyển sinh
            'annual_quota_view_any', // Xem danh sách chỉ tiêu năm
            'quota_view_any',        // Xem danh sách chỉ tiêu chi tiết
        ]);

        $this->command->info('Permissions and roles created successfully!');
    }
}
