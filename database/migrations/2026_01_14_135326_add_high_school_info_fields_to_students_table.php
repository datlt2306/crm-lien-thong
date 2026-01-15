<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->string('high_school_name')->nullable()->after('address');
            $table->string('high_school_code')->nullable()->after('high_school_name');
            $table->string('high_school_province')->nullable()->after('high_school_code');
            $table->string('high_school_province_code')->nullable()->after('high_school_province');
            $table->string('high_school_district')->nullable()->after('high_school_province_code');
            $table->string('high_school_district_code')->nullable()->after('high_school_district');
            $table->year('high_school_graduation_year')->nullable()->after('high_school_district_code');
            $table->string('high_school_academic_performance')->nullable()->after('high_school_graduation_year');
            $table->string('high_school_conduct')->nullable()->after('high_school_academic_performance');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'high_school_name',
                'high_school_code',
                'high_school_province',
                'high_school_province_code',
                'high_school_district',
                'high_school_district_code',
                'high_school_graduation_year',
                'high_school_academic_performance',
                'high_school_conduct',
            ]);
        });
    }
};
