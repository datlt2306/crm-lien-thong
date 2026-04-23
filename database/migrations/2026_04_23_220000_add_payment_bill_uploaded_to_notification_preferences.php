<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->boolean('email_payment_bill_uploaded')->default(true)->after('email_student_registered');
            $table->boolean('push_payment_bill_uploaded')->default(false)->after('push_student_registered');
            $table->boolean('in_app_payment_bill_uploaded')->default(true)->after('in_app_student_registered');
            $table->boolean('telegram_payment_bill_uploaded')->default(true)->after('telegram_student_registered');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'email_payment_bill_uploaded',
                'push_payment_bill_uploaded',
                'in_app_payment_bill_uploaded',
                'telegram_payment_bill_uploaded',
            ]);
        });
    }
};
