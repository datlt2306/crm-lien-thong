<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('quotas', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('intake_id'); // Thuộc đợt tuyển sinh nào
            $table->unsignedBigInteger('major_id'); // Ngành học
            $table->unsignedBigInteger('organization_id'); // Thuộc tổ chức nào
            $table->integer('target_quota')->default(0); // Chỉ tiêu mục tiêu
            $table->integer('current_quota')->default(0); // Chỉ tiêu hiện tại (đã nhập học)
            $table->integer('pending_quota')->default(0); // Chỉ tiêu đang chờ (đã nộp hồ sơ)
            $table->integer('reserved_quota')->default(0); // Chỉ tiêu đã đặt cọc
            $table->decimal('tuition_fee', 12, 2)->nullable(); // Học phí cho đợt này
            $table->text('notes')->nullable(); // Ghi chú
            $table->enum('status', ['active', 'inactive', 'full'])->default('active');
            $table->timestamps();

            $table->foreign('intake_id')->references('id')->on('intakes')->onDelete('cascade');
            $table->foreign('major_id')->references('id')->on('majors')->onDelete('cascade');
            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');

            // Unique constraint: mỗi đợt tuyển chỉ có 1 quota cho 1 ngành
            $table->unique(['intake_id', 'major_id', 'organization_id']);
            $table->index(['intake_id', 'status']);
            $table->index(['major_id', 'organization_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('quotas');
    }
};
