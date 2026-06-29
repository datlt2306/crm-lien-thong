<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payment_adjustments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('payments')->onDelete('cascade');
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('type'); // 'transfer', 'refund', 'discount', 'offset', 'adjustment'
            $table->decimal('amount', 15, 2);
            $table->text('reason')->nullable();
            $table->string('refund_status')->nullable(); // 'pending', 'completed'
            $table->string('refund_proof_path')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void {
        Schema::dropIfExists('payment_adjustments');
    }
};
