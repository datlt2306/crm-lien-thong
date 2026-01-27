<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Chỉ tiêu năm: 1 năm có target (vd 100 CNTT chính quy), chia linh hoạt cho nhiều đợt.
     * Khi đợt 1 tuyển đủ 100 → hết; đợt 1 chỉ 30 → 70 chuyển sang đợt sau.
     */
    public function up(): void {
        Schema::create('annual_quotas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('major_id')->constrained()->cascadeOnDelete();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('year'); // Năm tuyển sinh, VD: 2025
            $table->integer('target_quota')->default(0); // Chỉ tiêu cả năm
            $table->integer('current_quota')->default(0); // Đã tuyển (cộng dồn qua các đợt)
            $table->enum('status', ['active', 'inactive', 'full'])->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['organization_id', 'major_id', 'program_id', 'year']);
            $table->index(['organization_id', 'year', 'status']);
        });
    }

    public function down(): void {
        Schema::dropIfExists('annual_quotas');
    }
};
