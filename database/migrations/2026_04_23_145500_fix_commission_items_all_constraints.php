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
            // 1. Sửa Trigger Constraint
            DB::statement("ALTER TABLE commission_items DROP CONSTRAINT IF EXISTS commission_items_trigger_check");
            DB::statement("ALTER TABLE commission_items ADD CONSTRAINT commission_items_trigger_check CHECK (trigger IN ('payment_verified', 'student_enrolled', 'ON_VERIFICATION', 'ON_ENROLLMENT'))");

            // 2. Sửa Status Constraint (Đảm bảo hỗ trợ cả lowercase và uppercase cũ)
            DB::statement("ALTER TABLE commission_items DROP CONSTRAINT IF EXISTS commission_items_status_check");
            DB::statement("ALTER TABLE commission_items ADD CONSTRAINT commission_items_status_check CHECK (status IN ('pending', 'payable', 'paid', 'cancelled', 'payment_confirmed', 'received_confirmed', 'PENDING', 'PAYABLE', 'PAID'))");

            // 3. Sửa Visibility Constraint
            DB::statement("ALTER TABLE commission_items DROP CONSTRAINT IF EXISTS commission_items_visibility_check");
            DB::statement("ALTER TABLE commission_items ADD CONSTRAINT commission_items_visibility_check CHECK (visibility IN ('visible', 'hidden', 'INTERNAL', 'ORG_ONLY'))");
            
            // Đảm bảo default là các giá trị mới
            DB::statement("ALTER TABLE commission_items ALTER COLUMN status SET DEFAULT 'pending'");
            DB::statement("ALTER TABLE commission_items ALTER COLUMN visibility SET DEFAULT 'visible'");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        if (DB::getDriverName() === 'pgsql') {
            // Rollback về trạng thái cơ bản (nếu cần)
            DB::statement("ALTER TABLE commission_items DROP CONSTRAINT IF EXISTS commission_items_trigger_check");
            DB::statement("ALTER TABLE commission_items DROP CONSTRAINT IF EXISTS commission_items_status_check");
            DB::statement("ALTER TABLE commission_items DROP CONSTRAINT IF EXISTS commission_items_visibility_check");
        }
    }
};
