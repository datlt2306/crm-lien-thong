<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('commission_items', function (Blueprint $table) {
            $table->dropUnique('commission_items_commission_id_recipient_collaborator_id_unique');
        });
    }

    public function down(): void {
        Schema::table('commission_items', function (Blueprint $table) {
            $table->unique(['commission_id', 'recipient_collaborator_id']);
        });
    }
};
