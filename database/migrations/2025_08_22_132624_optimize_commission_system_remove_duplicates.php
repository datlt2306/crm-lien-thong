<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Tối ưu hóa hệ thống commission và loại bỏ trùng lặp
     */
    public function up(): void {
        // 1. Thêm index để tối ưu performance
        Schema::table('commission_items', function (Blueprint $table) {
            $table->index(['status', 'recipient_id']);
            $table->index(['status', 'trigger']);
            $table->index(['payable_at']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->index(['status', 'student_id']);
            $table->index(['verified_at']);
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->index(['wallet_id', 'created_at']);
            $table->index(['transaction_type']);
        });

        Schema::table('downline_commission_configs', function (Blueprint $table) {
            $table->index(['upline_collaborator_id', 'is_active']);
            $table->index(['payment_type']);
        });

        // 2. Thêm các trường để tối ưu hóa
        Schema::table('commission_items', function (Blueprint $table) {
            $table->decimal('original_amount', 15, 2)->nullable()->after('amount')
                ->comment('Số tiền gốc trước khi điều chỉnh');
            $table->text('notes')->nullable()->after('paid_at')
                ->comment('Ghi chú về commission');
        });

        Schema::table('wallets', function (Blueprint $table) {
            $table->decimal('pending_balance', 15, 2)->default(0)->after('balance')
                ->comment('Số dư đang chờ (commission pending)');
            $table->decimal('available_balance', 15, 2)->default(0)->after('pending_balance')
                ->comment('Số dư có thể sử dụng (commission payable)');
        });

        // 3. Thêm bảng audit log cho commission
        Schema::create('commission_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_item_id')->constrained()->onDelete('cascade');
            $table->string('action'); // created, status_changed, paid, cancelled
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
    }

    public function down(): void {
        // Xóa bảng audit log
        Schema::dropIfExists('commission_audit_logs');

        // Xóa các trường đã thêm
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropColumn(['pending_balance', 'available_balance']);
        });

        Schema::table('commission_items', function (Blueprint $table) {
            $table->dropColumn(['original_amount', 'notes']);
        });

        // Xóa các index
        Schema::table('downline_commission_configs', function (Blueprint $table) {
            $table->dropIndex(['upline_collaborator_id', 'is_active']);
            $table->dropIndex(['payment_type']);
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropIndex(['wallet_id', 'created_at']);
            $table->dropIndex(['transaction_type']);
        });

        Schema::table('payments', function (Blueprint $table) {
            $table->dropIndex(['status', 'student_id']);
            $table->dropIndex(['verified_at']);
        });

        Schema::table('commission_items', function (Blueprint $table) {
            $table->dropIndex(['status', 'recipient_id']);
            $table->dropIndex(['status', 'trigger']);
            $table->dropIndex(['payable_at']);
        });
    }
};
