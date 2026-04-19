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
            $indexes = Schema::getIndexes('students');
            $indexNames = array_column($indexes, 'name');

            if (!in_array('students_collaborator_id_index', $indexNames)) {
                $table->index('collaborator_id');
            }
            if (!in_array('students_intake_id_index', $indexNames)) {
                $table->index('intake_id');
            }
            if (!in_array('students_major_index', $indexNames)) {
                $table->index('major');
            }
            if (!in_array('students_status_index', $indexNames)) {
                $table->index('status');
            }
            if (!in_array('students_application_status_index', $indexNames)) {
                $table->index('application_status');
            }
        });

        Schema::table('payments', function (Blueprint $table) {
            $indexes = Schema::getIndexes('payments');
            $indexNames = array_column($indexes, 'name');

            if (!in_array('payments_student_id_index', $indexNames)) {
                $table->index('student_id');
            }
            if (!in_array('payments_status_index', $indexNames)) {
                $table->index('status');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $indexes = Schema::getIndexes('students');
            $indexNames = array_column($indexes, 'name');

            if (in_array('students_collaborator_id_index', $indexNames)) $table->dropIndex(['collaborator_id']);
            if (in_array('students_intake_id_index', $indexNames)) $table->dropIndex(['intake_id']);
            if (in_array('students_major_index', $indexNames)) $table->dropIndex(['major']);
            if (in_array('students_status_index', $indexNames)) $table->dropIndex(['status']);
            if (in_array('students_application_status_index', $indexNames)) $table->dropIndex(['application_status']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $indexes = Schema::getIndexes('payments');
            $indexNames = array_column($indexes, 'name');

            if (in_array('payments_student_id_index', $indexNames)) $table->dropIndex(['student_id']);
            if (in_array('payments_status_index', $indexNames)) $table->dropIndex(['status']);
        });
    }
};
