<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        if (Schema::hasColumn('students', 'current_college')) {
            Schema::table('students', function (Blueprint $table) {
                $table->dropColumn('current_college');
            });
        }
    }

    public function down(): void {
        if (!Schema::hasColumn('students', 'current_college')) {
            Schema::table('students', function (Blueprint $table) {
                $table->string('current_college')->nullable();
            });
        }
    }
};
