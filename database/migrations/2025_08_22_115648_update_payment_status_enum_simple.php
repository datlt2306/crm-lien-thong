<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
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
        Schema::table('payments', function (Blueprint $table) {
            $table->enum('status', ['SUBMITTED', 'VERIFIED', 'REJECTED'])->default('SUBMITTED')->change();
        });
    }
};
