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
                // BƯỚC 1: XÓA TẤT CẢ CONSTRAINT CŨ
                DB::statement("ALTER TABLE commission_items DROP CONSTRAINT IF EXISTS commission_items_status_check");
                DB::statement("ALTER TABLE commission_items DROP CONSTRAINT IF EXISTS commission_items_role_check");
                DB::statement("ALTER TABLE commission_items DROP CONSTRAINT IF EXISTS commission_items_trigger_check");
                DB::statement("ALTER TABLE commission_items DROP CONSTRAINT IF EXISTS commission_items_visibility_check");

                DB::statement("ALTER TABLE commission_policies DROP CONSTRAINT IF EXISTS commission_policies_role_check");
                DB::statement("ALTER TABLE commission_policies DROP CONSTRAINT IF EXISTS commission_policies_type_check");
                DB::statement("ALTER TABLE commission_policies DROP CONSTRAINT IF EXISTS commission_policies_trigger_check");
                DB::statement("ALTER TABLE commission_policies DROP CONSTRAINT IF EXISTS commission_policies_visibility_check");

                // BƯỚC 2: CHUẨN HÓA DỮ LIỆU
                DB::statement("UPDATE commission_items SET 
                    status = LOWER(status), 
                    role = LOWER(role), 
                    trigger = LOWER(trigger), 
                    visibility = LOWER(visibility)");

                DB::statement("UPDATE commission_policies SET 
                    role = LOWER(role), 
                    type = LOWER(type), 
                    trigger = LOWER(trigger), 
                    visibility = LOWER(visibility)");

                // BƯỚC 3: THIẾT LẬP LẠI CONSTRAINT MỚI
                DB::statement("ALTER TABLE commission_items ADD CONSTRAINT commission_items_status_check CHECK (status IN ('pending', 'payable', 'paid', 'cancelled', 'payment_confirmed', 'received_confirmed'))");
                DB::statement("ALTER TABLE commission_items ADD CONSTRAINT commission_items_role_check CHECK (role IN ('primary', 'sub', 'direct'))");
                DB::statement("ALTER TABLE commission_items ADD CONSTRAINT commission_items_trigger_check CHECK (trigger IN ('payment_verified', 'student_enrolled', 'on_verification', 'on_enrollment'))");
                DB::statement("ALTER TABLE commission_items ADD CONSTRAINT commission_items_visibility_check CHECK (visibility IN ('visible', 'hidden', 'internal', 'org_only'))");

                DB::statement("ALTER TABLE commission_policies ADD CONSTRAINT commission_policies_role_check CHECK (role IN ('primary', 'sub', 'direct'))");
                DB::statement("ALTER TABLE commission_policies ADD CONSTRAINT commission_policies_type_check CHECK (type IN ('fixed', 'percent'))");
                DB::statement("ALTER TABLE commission_policies ADD CONSTRAINT commission_policies_trigger_check CHECK (trigger IN ('payment_verified', 'student_enrolled', 'on_verification', 'on_enrollment'))");
                DB::statement("ALTER TABLE commission_policies ADD CONSTRAINT commission_policies_visibility_check CHECK (visibility IN ('visible', 'hidden', 'internal', 'org_only'))");

                // BƯỚC 4: THIẾT LẬP DEFAULT
                DB::statement("ALTER TABLE commission_items ALTER COLUMN status SET DEFAULT 'pending'");
                DB::statement("ALTER TABLE commission_items ALTER COLUMN visibility SET DEFAULT 'visible'");
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
    }
};
