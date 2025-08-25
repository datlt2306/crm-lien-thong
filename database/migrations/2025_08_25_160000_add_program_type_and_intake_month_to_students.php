<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('students', function (Blueprint $table) {
            if (!Schema::hasColumn('students', 'program_type')) {
                $table->enum('program_type', ['REGULAR', 'PART_TIME'])->nullable()->after('major');
            }
            if (!Schema::hasColumn('students', 'intake_month')) {
                $table->unsignedTinyInteger('intake_month')->nullable()->after('program_type');
            }
        });
    }

    public function down(): void {
        Schema::table('students', function (Blueprint $table) {
            if (Schema::hasColumn('students', 'intake_month')) {
                $table->dropColumn('intake_month');
            }
            if (Schema::hasColumn('students', 'program_type')) {
                $table->dropColumn('program_type');
            }
        });
    }
};
