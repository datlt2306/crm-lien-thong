<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('commission_items', function (Blueprint $table) {
            // Thêm trạng thái mới cho quy trình 2 bước
            $table->enum('status', ['pending', 'payable', 'paid', 'cancelled', 'payment_confirmed', 'received_confirmed'])->default('pending')->change();

            // Thêm trường cho bill thanh toán
            $table->string('payment_bill_path')->nullable()->after('status')->comment('Đường dẫn bill thanh toán');
            $table->timestamp('payment_confirmed_at')->nullable()->after('payment_bill_path')->comment('Thời gian chủ đơn vị xác nhận thanh toán');
            $table->unsignedBigInteger('payment_confirmed_by')->nullable()->after('payment_confirmed_at')->comment('User ID chủ đơn vị xác nhận thanh toán');
            $table->foreign('payment_confirmed_by')->references('id')->on('users')->onDelete('set null');

            // Thêm trường cho xác nhận nhận tiền của CTV
            $table->timestamp('received_confirmed_at')->nullable()->after('payment_confirmed_by')->comment('Thời gian CTV xác nhận đã nhận tiền');
            $table->unsignedBigInteger('received_confirmed_by')->nullable()->after('received_confirmed_at')->comment('User ID CTV xác nhận đã nhận tiền');
            $table->foreign('received_confirmed_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('commission_items', function (Blueprint $table) {
            $table->dropForeign(['payment_confirmed_by']);
            $table->dropForeign(['received_confirmed_by']);
            $table->dropColumn(['payment_bill_path', 'payment_confirmed_at', 'payment_confirmed_by', 'received_confirmed_at', 'received_confirmed_by']);
            $table->enum('status', ['pending', 'payable', 'paid', 'cancelled'])->default('pending')->change();
        });
    }
};
