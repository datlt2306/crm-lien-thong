<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('intakes', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Tên đợt tuyển sinh (VD: "Đợt 1 - 2025", "Học kỳ I - 2025")
            $table->text('description')->nullable(); // Mô tả đợt tuyển sinh
            $table->date('start_date'); // Ngày bắt đầu tuyển sinh
            $table->date('end_date'); // Ngày kết thúc tuyển sinh
            $table->date('enrollment_deadline')->nullable(); // Hạn chót nhập học
            $table->enum('status', ['upcoming', 'active', 'closed', 'cancelled'])->default('upcoming');
            $table->unsignedBigInteger('organization_id'); // Thuộc tổ chức nào
            $table->json('settings')->nullable(); // Cài đặt bổ sung (VD: yêu cầu giấy tờ, phí tuyển sinh)
            $table->timestamps();

            $table->foreign('organization_id')->references('id')->on('organizations')->onDelete('cascade');
            $table->index(['organization_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('intakes');
    }
};
