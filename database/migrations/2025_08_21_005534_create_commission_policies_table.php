<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('commission_policies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->onDelete('cascade');
            $table->foreignId('collaborator_id')->nullable()->constrained()->onDelete('cascade');
            $table->enum('program_type', ['REGULAR', 'PART_TIME'])->nullable();
            $table->enum('role', ['PRIMARY', 'SUB'])->nullable();
            $table->enum('type', ['FIXED', 'PERCENT', 'PASS_THROUGH']);
            $table->decimal('amount_vnd', 12, 2)->nullable();
            $table->decimal('percent', 5, 2)->nullable();
            $table->enum('trigger', ['ON_VERIFICATION', 'ON_ENROLLMENT'])->nullable();
            $table->enum('visibility', ['INTERNAL', 'ORG_ONLY'])->nullable();
            $table->integer('priority')->default(0);
            $table->boolean('active')->default(true);
            $table->timestamp('effective_from')->nullable();
            $table->timestamp('effective_to')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('commission_policies');
    }
};
