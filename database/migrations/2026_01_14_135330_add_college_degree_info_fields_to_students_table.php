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
            // Thông tin Cao đẳng
            $table->string('college_graduation_school')->nullable()->after('high_school_conduct');
            $table->string('college_graduation_major')->nullable()->after('college_graduation_school');
            $table->string('college_graduation_grade')->nullable()->after('college_graduation_major');
            $table->string('college_training_type')->nullable()->after('college_graduation_grade');
            $table->year('college_graduation_year')->nullable()->after('college_training_type');
            $table->string('college_diploma_number')->nullable()->after('college_graduation_year');
            $table->string('college_diploma_book_number')->nullable()->after('college_diploma_number');
            $table->date('college_diploma_issue_date')->nullable()->after('college_diploma_book_number');
            $table->string('college_diploma_signer')->nullable()->after('college_diploma_issue_date');
            
            // Thông tin Trung cấp
            $table->string('intermediate_graduation_school')->nullable()->after('college_diploma_signer');
            $table->string('intermediate_graduation_major')->nullable()->after('intermediate_graduation_school');
            $table->string('intermediate_graduation_grade')->nullable()->after('intermediate_graduation_major');
            $table->string('intermediate_training_type')->nullable()->after('intermediate_graduation_grade');
            $table->year('intermediate_graduation_year')->nullable()->after('intermediate_training_type');
            $table->string('intermediate_diploma_number')->nullable()->after('intermediate_graduation_year');
            $table->string('intermediate_diploma_book_number')->nullable()->after('intermediate_diploma_number');
            $table->date('intermediate_diploma_issue_date')->nullable()->after('intermediate_diploma_book_number');
            $table->string('intermediate_diploma_signer')->nullable()->after('intermediate_diploma_issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'college_graduation_school',
                'college_graduation_major',
                'college_graduation_grade',
                'college_training_type',
                'college_graduation_year',
                'college_diploma_number',
                'college_diploma_book_number',
                'college_diploma_issue_date',
                'college_diploma_signer',
                'intermediate_graduation_school',
                'intermediate_graduation_major',
                'intermediate_graduation_grade',
                'intermediate_training_type',
                'intermediate_graduation_year',
                'intermediate_diploma_number',
                'intermediate_diploma_book_number',
                'intermediate_diploma_issue_date',
                'intermediate_diploma_signer',
            ]);
        });
    }
};
