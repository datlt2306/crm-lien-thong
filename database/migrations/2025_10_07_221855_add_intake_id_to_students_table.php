<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('students', function (Blueprint $table) {
            $table->unsignedBigInteger('intake_id')->nullable()->after('major_id');
            $table->foreign('intake_id')->references('id')->on('intakes')->onDelete('set null');
            $table->index(['intake_id', 'major_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['intake_id']);
            $table->dropIndex(['intake_id', 'major_id']);
            $table->dropColumn('intake_id');
        });
    }
};
