<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        // Add telegram_chat_id to users
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'telegram_chat_id')) {
                $table->string('telegram_chat_id')->nullable()->after('phone');
            }
        });

        // Add telegram preferences and student_registered to notification_preferences
        Schema::table('notification_preferences', function (Blueprint $table) {
            // New type: student_registered for existing channels
            if (!Schema::hasColumn('notification_preferences', 'email_student_registered')) {
                $table->boolean('email_student_registered')->default(true)->after('email_system_updates');
            }
            if (!Schema::hasColumn('notification_preferences', 'push_student_registered')) {
                $table->boolean('push_student_registered')->default(false)->after('push_system_updates');
            }
            if (!Schema::hasColumn('notification_preferences', 'in_app_student_registered')) {
                $table->boolean('in_app_student_registered')->default(true)->after('in_app_system_updates');
            }

            // Telegram channel preferences
            $table->boolean('telegram_payment_verified')->default(true);
            $table->boolean('telegram_payment_rejected')->default(true);
            $table->boolean('telegram_commission_earned')->default(true);
            $table->boolean('telegram_quota_warning')->default(true);
            $table->boolean('telegram_student_status_change')->default(false);
            $table->boolean('telegram_system_updates')->default(false);
            $table->boolean('telegram_student_registered')->default(true);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('telegram_chat_id');
        });

        Schema::table('notification_preferences', function (Blueprint $table) {
            $table->dropColumn([
                'email_student_registered',
                'push_student_registered',
                'in_app_student_registered',
                'telegram_payment_verified',
                'telegram_payment_rejected',
                'telegram_commission_earned',
                'telegram_quota_warning',
                'telegram_student_status_change',
                'telegram_system_updates',
                'telegram_student_registered',
            ]);
        });
    }
};
