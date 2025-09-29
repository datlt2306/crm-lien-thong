<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            // Drop the existing enum constraint
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            // Add the new enum with accountant role
            $table->enum('role', ['super_admin', 'chủ đơn vị', 'ctv', 'kế toán'])->default('ctv');
        });
    }

    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            // Drop the new enum constraint
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            // Restore the previous enum without accountant
            $table->enum('role', ['super_admin', 'chủ đơn vị', 'ctv'])->default('ctv');
        });
    }
};
