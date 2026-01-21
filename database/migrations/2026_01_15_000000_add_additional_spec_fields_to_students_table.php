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
            // I. Thông tin cơ bản – GVHD
            $table->string('instructor')->nullable()->after('address');

            // IV. Hồ sơ học tập – Trung cấp (bằng + bảng điểm)
            $table->string('document_intermediate_diploma')->nullable()->after('intermediate_diploma_signer');
            $table->string('document_intermediate_transcript')->nullable()->after('document_intermediate_diploma');

            // V. Giấy tờ cá nhân – phân biệt BS/BG
            $table->string('college_diploma_copy_type', 10)->nullable()->after('college_diploma_signer');
            $table->string('college_transcript_copy_type', 10)->nullable()->after('college_diploma_copy_type');
            $table->string('high_school_diploma_copy_type', 10)->nullable()->after('document_high_school_diploma');
            $table->string('birth_certificate_copy_type', 10)->nullable()->after('document_birth_certificate');
            $table->string('health_certificate_copy_type', 10)->nullable()->after('document_health_certificate');

            // VII. Thông tin khu vực – ưu tiên
            $table->string('priority_area')->nullable()->after('high_school_district_code');

            // IX. Tuyển sinh & trạng thái hồ sơ
            $table->string('application_status')->nullable()->after('status');
            $table->unsignedInteger('fee')->nullable()->after('application_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'instructor',
                'document_intermediate_diploma',
                'document_intermediate_transcript',
                'college_diploma_copy_type',
                'college_transcript_copy_type',
                'high_school_diploma_copy_type',
                'birth_certificate_copy_type',
                'health_certificate_copy_type',
                'priority_area',
                'application_status',
                'fee',
            ]);
        });
    }
};

