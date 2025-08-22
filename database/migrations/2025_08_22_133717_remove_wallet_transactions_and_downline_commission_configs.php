<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Loại bỏ wallet_transactions và downline_commission_configs
     */
    public function up(): void {
        // 1. Xóa bảng wallet_transactions
        Schema::dropIfExists('wallet_transactions');

        // 2. Xóa bảng downline_commission_configs
        Schema::dropIfExists('downline_commission_configs');

        // 3. Xóa bảng commission_audit_logs
        Schema::dropIfExists('commission_audit_logs');

        // 4. Xóa các trường liên quan trong wallets
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['pending_balance', 'available_balance']);
        });

        // 5. Xóa các trường liên quan trong commission_items
        Schema::table('commission_items', function (Blueprint $table) {
            $table->dropColumn(['original_amount', 'notes']);
        });
    }

    public function down(): void {
        // 1. Tạo lại bảng commission_audit_logs
        Schema::create('commission_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_item_id')->constrained()->onDelete('cascade');
            $table->string('action');
            $table->string('old_status')->nullable();
            $table->string('new_status')->nullable();
            $table->decimal('old_amount', 15, 2)->nullable();
            $table->decimal('new_amount', 15, 2)->nullable();
            $table->foreignId('performed_by')->nullable()->constrained('users')->onDelete('set null');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['commission_item_id', 'created_at']);
            $table->index(['action', 'created_at']);
        });

        // 2. Thêm lại các trường trong commission_items
        Schema::table('commission_items', function (Blueprint $table) {
            $table->decimal('original_amount', 15, 2)->nullable()->after('amount')
                ->comment('Số tiền gốc trước khi điều chỉnh');
            $table->text('notes')->nullable()->after('paid_at')
                ->comment('Ghi chú về commission');
        });

        // 3. Thêm lại các trường trong wallets
        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('pending_balance', 15, 2)->default(0)->after('balance')
                ->comment('Số dư đang chờ (commission pending)');
            $table->decimal('available_balance', 15, 2)->default(0)->after('pending_balance')
                ->comment('Số dư có thể sử dụng (commission payable)');
        });

        // 4. Tạo lại bảng downline_commission_configs
        Schema::create('downline_commission_configs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('upline_collaborator_id')->constrained('collaborators')->onDelete('cascade');
            $table->foreignId('downline_collaborator_id')->constrained('collaborators')->onDelete('cascade');
            $table->decimal('cq_amount', 15, 2)->default(0);
            $table->decimal('vhvlv_amount', 15, 2)->default(0);
            $table->enum('payment_type', ['immediate', 'on_enrollment'])->default('immediate');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            $table->unique(['upline_collaborator_id', 'downline_collaborator_id']);
            $table->index(['upline_collaborator_id', 'is_active']);
            $table->index(['payment_type']);
        });

        // 5. Tạo lại bảng wallet_transactions
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained()->onDelete('cascade');
            $table->decimal('amount', 15, 2);
            $table->enum('type', ['credit', 'debit']);
            $table->string('transaction_type');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();
            
            $table->index(['wallet_id', 'created_at']);
            $table->index(['transaction_type']);
        });
    }
};
