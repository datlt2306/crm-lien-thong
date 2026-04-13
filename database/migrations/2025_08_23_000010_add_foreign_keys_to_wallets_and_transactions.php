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
        Schema::table('wallets', function (Blueprint $table) {
            $table
                ->foreign('collaborator_id')
                ->references('id')
                ->on('collaborators')
                ->cascadeOnDelete();
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table
                ->foreign('commission_item_id')
                ->references('id')
                ->on('commission_items')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropForeign(['commission_item_id']);
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->dropForeign(['collaborator_id']);
        });
    }
};
