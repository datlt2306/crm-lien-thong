<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        // Tạo lại bảng commission_items với cấu trúc đúng
        Schema::dropIfExists('commission_items');

        Schema::create('commission_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_id')->constrained()->onDelete('cascade');
            $table->foreignId('recipient_collaborator_id')->constrained('collaborators')->onDelete('cascade');
            $table->enum('role', ['direct', 'downline'])->default('direct');
            $table->decimal('amount', 15, 2);
            $table->enum('status', ['pending', 'payable', 'paid', 'cancelled', 'payment_confirmed', 'received_confirmed'])->default('pending');
            $table->string('trigger')->nullable();
            $table->timestamp('payable_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->string('payment_bill_path')->nullable();
            $table->timestamp('payment_confirmed_at')->nullable();
            $table->foreignId('payment_confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('received_confirmed_at')->nullable();
            $table->foreignId('received_confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->enum('visibility', ['visible', 'hidden'])->default('visible');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['recipient_collaborator_id', 'status']);
            $table->index(['status', 'payable_at']);
            $table->index(['commission_id', 'role']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('commission_items');
    }
};
