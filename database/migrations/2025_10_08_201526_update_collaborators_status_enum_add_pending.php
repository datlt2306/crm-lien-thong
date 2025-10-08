<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('collaborators', function (Blueprint $table) {
            // Thay đổi enum status để thêm 'pending'
            $table->enum('status', ['active', 'pending', 'inactive'])->default('active')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('collaborators', function (Blueprint $table) {
            // Trở lại enum cũ
            $table->enum('status', ['active', 'inactive'])->default('active')->change();
        });
    }
};
