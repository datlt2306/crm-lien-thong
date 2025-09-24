<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('receipt_path')->nullable()->after('bill_path');
            $table->foreignId('receipt_uploaded_by')->nullable()->after('receipt_path')->constrained('users')->nullOnDelete();
            $table->timestamp('receipt_uploaded_at')->nullable()->after('receipt_uploaded_by');
        });
    }

    public function down(): void {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['receipt_path', 'receipt_uploaded_by', 'receipt_uploaded_at']);
        });
    }
};
