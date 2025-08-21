<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('collaborator_id')->constrained('collaborators')->onDelete('cascade');
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('total_received', 15, 2)->default(0);
            $table->decimal('total_paid', 15, 2)->default(0);
            $table->timestamps();

            // Mỗi collaborator chỉ có 1 wallet
            $table->unique('collaborator_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('wallets');
    }
};
