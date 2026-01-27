<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * SQLite không hỗ trợ ENUM, status đã là VARCHAR nên không cần modify.
     * Migration này chỉ cần ensure cột status tồn tại.
     */
    public function up(): void {
        // SQLite/Laravel dùng string cho status, không cần alter
        // Trạng thái 'reverted' sẽ được chấp nhận tự động vì cột là string
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        // Không cần rollback vì không thay đổi schema
    }
};
