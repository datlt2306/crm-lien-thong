<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // Drop existing unique constraints
            // We use DB::statement for dropping because Laravel might use different names 
            // but we confirmed them with Schema::getIndexes
            $table->dropUnique('students_phone_unique');
            $table->dropUnique('students_email_unique');
            $table->dropUnique('students_identity_card_unique');
            $table->dropUnique('students_profile_code_unique');
        });

        // Create partial unique indexes (PostgreSQL specific)
        DB::statement('CREATE UNIQUE INDEX students_phone_unique ON students (phone) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX students_email_unique ON students (email) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX students_identity_card_unique ON students (identity_card) WHERE deleted_at IS NULL');
        DB::statement('CREATE UNIQUE INDEX students_profile_code_unique ON students (profile_code) WHERE deleted_at IS NULL');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Drop partial indexes
        DB::statement('DROP INDEX IF EXISTS students_phone_unique');
        DB::statement('DROP INDEX IF EXISTS students_email_unique');
        DB::statement('DROP INDEX IF EXISTS students_identity_card_unique');
        DB::statement('DROP INDEX IF EXISTS students_profile_code_unique');

        // Restore standard unique constraints
        Schema::table('students', function (Blueprint $table) {
            $table->unique('phone');
            $table->unique('email');
            $table->unique('identity_card');
            $table->unique('profile_code');
        });
    }
};
