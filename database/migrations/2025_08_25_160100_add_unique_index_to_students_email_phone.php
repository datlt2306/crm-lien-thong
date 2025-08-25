<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        // Chỉ tạo unique index cho email nếu không có dữ liệu trùng
        $hasDuplicates = DB::table('students')
            ->select('email', DB::raw('COUNT(*) as cnt'))
            ->whereNotNull('email')
            ->groupBy('email')
            ->having('cnt', '>', 1)
            ->exists();

        if (!$hasDuplicates && Schema::hasColumn('students', 'email')) {
            Schema::table('students', function (Blueprint $table) {
                // Một số DB đã có unique, tránh tạo trùng
                try {
                    $table->unique('email');
                } catch (\Throwable $e) {
                    // Bỏ qua nếu index đã tồn tại/không hỗ trợ
                }
            });
        }
    }

    public function down(): void {
        if (Schema::hasColumn('students', 'email')) {
            Schema::table('students', function (Blueprint $table) {
                try {
                    $table->dropUnique(['email']);
                } catch (\Throwable $e) {
                    // Bỏ qua nếu index không tồn tại
                }
            });
        }
    }
};
