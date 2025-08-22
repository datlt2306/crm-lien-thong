<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('students', function (Blueprint $table) {
            // Thay đổi enum status để phù hợp với pipeline mới
            $table->enum('status', [
                'new',           // Mới
                'contacted',     // Đã liên hệ
                'submitted',     // Đã nộp hồ sơ
                'approved',      // Đã duyệt
                'enrolled',      // Đã nhập học
                'rejected'       // Từ chối
            ])->default('new')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('students', function (Blueprint $table) {
            // Khôi phục enum status cũ
            $table->enum('status', [
                'new', 'contacted', 'submitted', 'approved', 'enrolled', 'rejected', 
                'pending', 'interviewed', 'deposit_paid', 'offer_sent', 'offer_accepted'
            ])->default('new')->change();
        });
    }
};
