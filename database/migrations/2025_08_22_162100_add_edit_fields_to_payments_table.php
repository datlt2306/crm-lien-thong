<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('payments', function (Blueprint $table) {
            $table->text('edit_reason')->nullable()->after('bill_path')->comment('Lý do chỉnh sửa bill');
            $table->timestamp('edited_at')->nullable()->after('edit_reason')->comment('Thời gian chỉnh sửa');
            $table->unsignedBigInteger('edited_by')->nullable()->after('edited_at')->comment('User ID người chỉnh sửa');

            $table->foreign('edited_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropForeign(['edited_by']);
            $table->dropColumn(['edit_reason', 'edited_at', 'edited_by']);
        });
    }
};
