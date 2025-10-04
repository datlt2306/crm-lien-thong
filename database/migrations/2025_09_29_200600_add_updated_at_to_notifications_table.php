<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        // Bảng notifications đã có cột updated_at từ migration create_notifications_table
        // Không cần thêm cột này nữa
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        // Không cần rollback vì cột updated_at đã có sẵn trong bảng notifications
    }
};
