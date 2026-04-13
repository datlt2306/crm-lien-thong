<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("UPDATE payments SET status = lower(status)");
            DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('not_paid', 'submitted', 'verified'))");
            DB::statement("ALTER TABLE payments ALTER COLUMN status SET DEFAULT 'not_paid'");
            return;
        }

        // Thay đổi enum status của bảng payments
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', [
                'not_paid',      // Chưa nộp tiền
                'submitted',     // Đã nộp (chờ xác minh)
                'verified'       // Đã xác nhận
            ])->default('not_paid')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("UPDATE payments SET status = upper(status)");
            DB::statement("ALTER TABLE payments DROP CONSTRAINT IF EXISTS payments_status_check");
            DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_status_check CHECK (status IN ('SUBMITTED', 'VERIFIED', 'REJECTED'))");
            DB::statement("ALTER TABLE payments ALTER COLUMN status SET DEFAULT 'SUBMITTED'");
            return;
        }

        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['SUBMITTED', 'VERIFIED', 'REJECTED'])->default('SUBMITTED')->change();
        });
    }
};
