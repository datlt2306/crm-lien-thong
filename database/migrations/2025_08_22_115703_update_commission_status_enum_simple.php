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
            DB::statement("UPDATE commission_items SET status = lower(status)");
            DB::statement("ALTER TABLE commission_items DROP CONSTRAINT IF EXISTS commission_items_status_check");
            DB::statement("ALTER TABLE commission_items ADD CONSTRAINT commission_items_status_check CHECK (status IN ('pending', 'payable', 'paid', 'cancelled'))");
            DB::statement("ALTER TABLE commission_items ALTER COLUMN status SET DEFAULT 'pending'");
            return;
        }

        // Thay đổi enum status của bảng commission_items
        Schema::table('commission_items', function (Blueprint $table) {
            $table->enum('status', [
                'pending',       // Pending → đã sinh commission nhưng chưa đến hạn chi
                'payable',       // Payable → đến hạn chi, CTV có thể nhận
                'paid',          // Paid → đã chi trả (ghi nhận bằng tay, đính bill)
                'cancelled'      // Cancelled → huỷ (VD: SV không nhập học)
            ])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement("UPDATE commission_items SET status = upper(status)");
            DB::statement("ALTER TABLE commission_items DROP CONSTRAINT IF EXISTS commission_items_status_check");
            DB::statement("ALTER TABLE commission_items ADD CONSTRAINT commission_items_status_check CHECK (status IN ('PENDING', 'PAYABLE', 'PAID'))");
            DB::statement("ALTER TABLE commission_items ALTER COLUMN status SET DEFAULT 'PENDING'");
            return;
        }

        Schema::table('commission_items', function (Blueprint $table) {
            $table->enum('status', ['PENDING', 'PAYABLE', 'PAID'])->default('PENDING')->change();
        });
    }
};
