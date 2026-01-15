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
            $table->string('document_college_diploma')->nullable()->after('document_checklist');
            $table->string('document_college_transcript')->nullable()->after('document_college_diploma');
            $table->string('document_high_school_diploma')->nullable()->after('document_college_transcript');
            $table->string('document_birth_certificate')->nullable()->after('document_high_school_diploma');
            $table->string('document_identity_card_front')->nullable()->after('document_birth_certificate');
            $table->string('document_identity_card_back')->nullable()->after('document_identity_card_front');
            $table->string('document_photo')->nullable()->after('document_identity_card_back');
            $table->string('document_health_certificate')->nullable()->after('document_photo');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'document_college_diploma',
                'document_college_transcript',
                'document_high_school_diploma',
                'document_birth_certificate',
                'document_identity_card_front',
                'document_identity_card_back',
                'document_photo',
                'document_health_certificate',
            ]);
        });
    }
};
