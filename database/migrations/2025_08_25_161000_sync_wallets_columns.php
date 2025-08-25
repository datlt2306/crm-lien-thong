<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('wallets', function (Blueprint $table) {
            if (!Schema::hasColumn('wallets', 'balance')) {
                $table->decimal('balance', 15, 2)->default(0)->after('collaborator_id');
            }
            if (!Schema::hasColumn('wallets', 'total_received')) {
                $table->decimal('total_received', 15, 2)->default(0)->after('balance');
            }
            if (!Schema::hasColumn('wallets', 'total_paid')) {
                $table->decimal('total_paid', 15, 2)->default(0)->after('total_received');
            }
            // Unique đã có từ migration gốc, không tạo lại để tránh lỗi
        });
    }

    public function down(): void {
        Schema::table('wallets', function (Blueprint $table) {
            if (Schema::hasColumn('wallets', 'total_paid')) {
                $table->dropColumn('total_paid');
            }
            if (Schema::hasColumn('wallets', 'total_received')) {
                $table->dropColumn('total_received');
            }
        });
    }
};
