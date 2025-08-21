<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('major_organization', function (Blueprint $table) {
            // Lưu các tháng tuyển sinh dạng JSON (SQLite dùng TEXT)
            $table->text('intake_months')->nullable()->after('quota');
        });
    }

    public function down(): void {
        Schema::table('major_organization', function (Blueprint $table) {
            $table->dropColumn('intake_months');
        });
    }
};
