<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('commission_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_id')->constrained('commissions')->onDelete('cascade');
            $table->foreignId('recipient_collaborator_id')->constrained('collaborators')->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->text('reason')->nullable();
            $table->string('status')->default('payable'); // 'pending', 'payable', 'paid', 'cancelled', 'payment_confirmed', 'received_confirmed'
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->string('payment_bill_path')->nullable();
            $table->timestamp('payment_confirmed_at')->nullable();
            $table->foreignId('payment_confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamp('received_confirmed_at')->nullable();
            $table->foreignId('received_confirmed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('commission_adjustments');
    }
};
