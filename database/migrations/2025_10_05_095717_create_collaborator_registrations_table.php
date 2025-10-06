<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('collaborator_registrations', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->string('ref_id', 8)->nullable(); // Sẽ được sinh khi approve
            $table->foreignId('upline_id')->nullable()->constrained('collaborators')->nullOnDelete();
            $table->text('note')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->text('rejection_reason')->nullable(); // Lý do từ chối
            $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete(); // Admin review
            $table->timestamp('reviewed_at')->nullable(); // Thời gian review
            $table->timestamps();

            // Indexes
            $table->index(['status', 'created_at']);
            $table->index(['organization_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('collaborator_registrations');
    }
};
