<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
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
        Schema::table('commission_items', function (Blueprint $table) {
            $table->enum('status', ['PENDING', 'PAYABLE', 'PAID'])->default('PENDING')->change();
        });
    }
};
