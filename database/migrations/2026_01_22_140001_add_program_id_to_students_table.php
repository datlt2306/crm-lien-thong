<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'program_id')) {
                $table->foreignId('program_id')->nullable()->after('major_id')->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'program_id')) {
                $table->dropForeign(['program_id']);
                $table->dropColumn('program_id');
            }
        });
    }
};
