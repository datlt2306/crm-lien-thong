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
            $table->string('birth_place')->nullable()->after('dob');
            $table->text('permanent_residence')->nullable()->after('birth_place');
            $table->string('ethnicity')->nullable()->after('permanent_residence');
            $table->enum('gender', ['male', 'female', 'other'])->nullable()->after('ethnicity');
            $table->date('identity_card_issue_date')->nullable()->after('identity_card');
            $table->string('identity_card_issue_place')->nullable()->after('identity_card_issue_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn([
                'birth_place',
                'permanent_residence',
                'ethnicity',
                'gender',
                'identity_card_issue_date',
                'identity_card_issue_place',
            ]);
        });
    }
};
