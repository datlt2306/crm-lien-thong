<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('commission_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipient_collaborator_id')->constrained('collaborators')->onDelete('cascade');
            $table->enum('role', ['PRIMARY', 'SUB']);
            $table->decimal('amount', 12, 2);
            $table->enum('status', ['PENDING', 'PAYABLE', 'PAID'])->default('PENDING');
            $table->enum('trigger', ['ON_VERIFICATION', 'ON_ENROLLMENT']);
            $table->timestamp('payable_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->enum('visibility', ['INTERNAL', 'ORG_ONLY'])->default('INTERNAL');
            $table->json('meta')->nullable();
            $table->timestamps();

            // Unique constraint
            $table->unique(['commission_id', 'recipient_collaborator_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('commission_items');
    }
};
