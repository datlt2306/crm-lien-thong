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
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->ulid('id')->primary();
            $table->string('event_group')->index(); // FINANCIAL, ACCOUNT_DELETION, SYSTEM
            $table->string('event_type')->index();  // CREATED, UPDATED, DELETED, etc.
            
            // Polymorphic relation to the target object
            $table->nullableMorphs('auditable');
            
            // Operator
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('user_role')->nullable();
            
            // Related Student (for easier filtering and access control)
            $table->foreignId('student_id')->nullable()->constrained('students')->onDelete('set null');
            
            // Data
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->decimal('amount_diff', 15, 2)->nullable();
            
            // Context
            $table->text('reason')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('metadata')->nullable(); // Snapshots or extra info
            
            $table->timestamp('created_at')->nullable()->useCurrent();
            
            // Add index for filtering
            $table->index(['event_group', 'created_at']);
            $table->index(['student_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
