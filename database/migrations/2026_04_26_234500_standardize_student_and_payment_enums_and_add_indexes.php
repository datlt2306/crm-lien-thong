<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::transaction(function () {
                // 1. CHUẨN HÓA BẢNG students
                DB::statement("ALTER TABLE students DROP CONSTRAINT IF EXISTS students_program_type_check");
                DB::statement("UPDATE students SET program_type = LOWER(program_type)");
                DB::statement("ALTER TABLE students ADD CONSTRAINT students_program_type_check CHECK (program_type IN ('regular', 'part_time', 'distance'))");

                // 2. CHUẨN HÓA BẢNG payments
                DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_program_type_check");
                DB::statement("UPDATE payments SET program_type = LOWER(program_type)");
                DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_program_type_check CHECK (program_type IN ('regular', 'part_time', 'distance'))");

                // 3. THÊM INDEXES TỐI ƯU HIỆU NĂNG
                // Index cho biểu đồ doanh thu Dashboard
                DB::statement("CREATE INDEX IF NOT EXISTS idx_payments_dashboard_stats ON payments (status, verified_at, program_type, primary_collaborator_id)");
                
                // Index cho việc tính tổng hoa hồng có thể thanh toán
                DB::statement("CREATE INDEX IF NOT EXISTS idx_commission_items_payable ON commission_items (status, payable_at)");
                
                // Index cho bộ lọc danh sách sinh viên
                DB::statement("CREATE INDEX IF NOT EXISTS idx_students_filter_status_program ON students (status, program_type)");
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("DROP INDEX IF EXISTS idx_payments_dashboard_stats");
            DB::statement("DROP INDEX IF EXISTS idx_commission_items_payable");
            DB::statement("DROP INDEX IF EXISTS idx_students_filter_status_program");
        }
    }
};
