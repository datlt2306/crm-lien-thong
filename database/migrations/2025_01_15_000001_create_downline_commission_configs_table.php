<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('downline_commission_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upline_collaborator_id')->constrained('collaborators')->onDelete('cascade');
            $table->foreignId('downline_collaborator_id')->constrained('collaborators')->onDelete('cascade');
            $table->decimal('cq_amount', 10, 2)->default(0); // Số tiền cho hệ Chính quy
            $table->decimal('vhvlv_amount', 10, 2)->default(0); // Số tiền cho hệ VHVLV
            $table->enum('payment_type', ['immediate', 'on_enrollment'])->default('immediate'); // Trả ngay hoặc trả khi nhập học
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Đảm bảo mỗi cặp upline-downline chỉ có 1 cấu hình
            $table->unique(['upline_collaborator_id', 'downline_collaborator_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('downline_commission_configs');
    }
};
