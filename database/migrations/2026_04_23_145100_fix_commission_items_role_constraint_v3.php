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
        if (DB::getDriverName() === 'pgsql') {
            // PostgreSQL sử dụng CHECK constraint cho ENUM được tạo qua Laravel Schema
            DB::statement("ALTER TABLE commission_items DROP CONSTRAINT IF EXISTS commission_items_role_check");
            
            // Thêm lại ràng buộc với đầy đủ các giá trị cần thiết
            DB::statement("ALTER TABLE commission_items ADD CONSTRAINT commission_items_role_check CHECK (role IN ('direct', 'override', 'downline', 'PRIMARY', 'SUB'))");
            
            // Đảm bảo default là direct
            DB::statement("ALTER TABLE commission_items ALTER COLUMN role SET DEFAULT 'direct'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE commission_items DROP CONSTRAINT IF EXISTS commission_items_role_check");
            DB::statement("ALTER TABLE commission_items ADD CONSTRAINT commission_items_role_check CHECK (role IN ('direct', 'downline'))");
        }
    }
};
