<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     * Bổ sung CCCD, mã số thuế, ngân hàng, tài khoản ngân hàng cho CTV (thanh toán hoa hồng).
     */
    public function up(): void {
        Schema::table('collaborators', function (Blueprint $table) {
            $table->string('identity_card', 20)->nullable()->after('email')->comment('Số CCCD');
            $table->string('tax_code', 20)->nullable()->after('identity_card')->comment('Mã số thuế');
            $table->string('bank_name')->nullable()->after('tax_code')->comment('Tên ngân hàng');
            $table->string('bank_account', 50)->nullable()->after('bank_name')->comment('Số tài khoản ngân hàng');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('collaborators', function (Blueprint $table) {
            $table->dropColumn(['identity_card', 'tax_code', 'bank_name', 'bank_account']);
        });
    }
};
