<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->onDelete('cascade');
            $table->foreignId('student_id')->unique()->constrained()->onDelete('cascade');
            $table->foreignId('primary_collaborator_id')->constrained('collaborators')->onDelete('cascade');
            $table->foreignId('sub_collaborator_id')->nullable()->constrained('collaborators')->onDelete('cascade');
            $table->enum('program_type', ['REGULAR', 'PART_TIME']);
            $table->decimal('amount', 12, 2);
            $table->string('bill_path')->nullable();
            $table->enum('status', ['SUBMITTED', 'VERIFIED', 'REJECTED'])->default('SUBMITTED');
            $table->foreignId('verified_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('payments');
    }
};
