<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('commission_items', function (Blueprint $table) {
            // Cập nhật enum status để hỗ trợ trạng thái mới
            $table->enum('status', ['pending', 'payable', 'paid'])->default('pending')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('commission_items', function (Blueprint $table) {
            $table->enum('status', ['pending', 'paid'])->default('pending')->change();
        });
    }
};
