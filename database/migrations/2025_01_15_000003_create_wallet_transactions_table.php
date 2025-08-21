<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->enum('type', ['deposit', 'withdrawal', 'transfer_out', 'transfer_in']);
            $table->decimal('amount', 15, 2);
            $table->decimal('balance_before', 15, 2);
            $table->decimal('balance_after', 15, 2);
            $table->string('description');
            $table->json('meta')->nullable(); // Lưu thông tin bổ sung
            $table->foreignId('related_wallet_id')->nullable()->constrained('wallets')->onDelete('set null'); // Wallet liên quan (cho transfer)
            $table->foreignId('commission_item_id')->nullable()->constrained('commission_items')->onDelete('set null'); // Liên kết với commission
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('wallet_transactions');
    }
};
