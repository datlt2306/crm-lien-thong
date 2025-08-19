<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('full_name');
            $table->string('phone')->unique();
            $table->string('email')->nullable();
            $table->foreignId('organization_id')->constrained('organizations')->cascadeOnDelete();
            $table->foreignId('collaborator_id')->nullable()->constrained('collaborators')->nullOnDelete();
            $table->string('current_college')->nullable();
            $table->string('target_university')->nullable();
            $table->string('major')->nullable();
            $table->date('dob')->nullable();
            $table->string('address')->nullable();
            $table->enum('source', ['form', 'ref', 'facebook', 'zalo', 'tiktok', 'hotline', 'event', 'school', 'walkin', 'other'])->default('form');
            $table->enum('status', ['new', 'contacted', 'submitted', 'approved', 'enrolled', 'rejected', 'pending', 'interviewed', 'deposit_paid', 'offer_sent', 'offer_accepted'])->default('new');
            $table->longText('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('students');
    }
};
