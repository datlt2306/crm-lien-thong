<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            // Drop the existing enum constraint
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            // Add the new enum with updated values
            $table->enum('role', ['super_admin', 'chủ đơn vị', 'ctv'])->default('ctv');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            // Drop the new enum constraint
            $table->dropColumn('role');
        });

        Schema::table('users', function (Blueprint $table) {
            // Restore the original enum
            $table->enum('role', ['super_admin', 'user'])->default('user');
        });
    }
};
