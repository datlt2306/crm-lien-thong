<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (!Schema::hasColumn('majors', 'code')) {
            return;
        }
        Schema::table('majors', function (Blueprint $table) {
            // Với SQLite, cần tạo lại bảng nếu có unique index; ở đây tạm set nullable thay vì drop để tránh lỗi
            $table->string('code')->nullable()->change();
        });
    }

    public function down(): void {
        Schema::table('majors', function (Blueprint $table) {
            $table->string('code')->nullable()->change();
        });
    }
};
