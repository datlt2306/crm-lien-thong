<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('payments', function (Blueprint $table) {
            // Cho phép primary_collaborator_id nullable để hỗ trợ sinh viên không có CTV
            $table->unsignedBigInteger('primary_collaborator_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('payments', function (Blueprint $table) {
            // Quay lại NOT NULL (cẩn thận nếu đang có bản ghi null)
            $table->unsignedBigInteger('primary_collaborator_id')->nullable(false)->change();
        });
    }
};

