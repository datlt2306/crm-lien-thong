<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('downline_commission_configs', function (Blueprint $table) {
            $table
                ->foreign('upline_collaborator_id')
                ->references('id')
                ->on('collaborators')
                ->cascadeOnDelete();

            $table
                ->foreign('downline_collaborator_id')
                ->references('id')
                ->on('collaborators')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('downline_commission_configs', function (Blueprint $table) {
            $table->dropForeign(['upline_collaborator_id']);
            $table->dropForeign(['downline_collaborator_id']);
        });
    }
};
