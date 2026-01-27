<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Bảng pivot liên kết Annual Quota với Intake.
     * Cho phép chỉ định chỉ tiêu năm áp dụng cho những đợt tuyển nào.
     */
    public function up(): void {
        Schema::create('annual_quota_intake', function (Blueprint $table) {
            $table->id();
            $table->foreignId('annual_quota_id')->constrained('annual_quotas')->cascadeOnDelete();
            $table->foreignId('intake_id')->constrained('intakes')->cascadeOnDelete();
            $table->timestamps();

            // Unique constraint: mỗi cặp annual_quota + intake chỉ tồn tại 1 lần
            $table->unique(['annual_quota_id', 'intake_id']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('annual_quota_intake');
    }
};
