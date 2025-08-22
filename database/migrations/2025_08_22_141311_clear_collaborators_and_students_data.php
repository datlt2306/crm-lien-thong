<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        // Xóa dữ liệu theo thứ tự để tránh lỗi foreign key constraint

        // 1. Xóa commission items liên quan đến students
        DB::table('commission_items')->whereNotNull('student_id')->delete();

        // 2. Xóa payments liên quan đến students
        DB::table('payments')->whereNotNull('student_id')->delete();

        // 3. Xóa students
        DB::table('students')->delete();

        // 4. Xóa commission items liên quan đến collaborators
        DB::table('commission_items')->whereNotNull('recipient_id')->delete();

        // 5. Xóa wallets liên quan đến collaborators
        DB::table('wallets')->whereNotNull('collaborator_id')->delete();

        // 6. Xóa collaborators
        DB::table('collaborators')->delete();

        // 7. Xóa users có role 'ctv' (cộng tác viên)
        DB::table('users')->where('role', 'ctv')->delete();

        // 8. Xóa commissions trống
        DB::table('commissions')->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        // Không thể khôi phục dữ liệu đã xóa
        // Migration này chỉ xóa dữ liệu, không thay đổi cấu trúc database
    }
};
