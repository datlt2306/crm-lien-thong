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
        Schema::table('collaborators', function (Blueprint $table) {
            if (Schema::hasColumn('collaborators', 'upline_id')) {
                $table->dropForeign(['upline_id']);
                $table->dropColumn('upline_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collaborators', function (Blueprint $table) {
            $table->foreignId('upline_id')->nullable()->constrained('collaborators')->onDelete('set null');
        });
    }
};
