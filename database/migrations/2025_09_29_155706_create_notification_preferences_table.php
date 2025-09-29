<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void {
        Schema::create('notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Email preferences
            $table->boolean('email_payment_verified')->default(true);
            $table->boolean('email_payment_rejected')->default(true);
            $table->boolean('email_commission_earned')->default(true);
            $table->boolean('email_quota_warning')->default(true);
            $table->boolean('email_student_status_change')->default(true);
            $table->boolean('email_system_updates')->default(true);

            // Push notification preferences
            $table->boolean('push_payment_verified')->default(true);
            $table->boolean('push_payment_rejected')->default(true);
            $table->boolean('push_commission_earned')->default(true);
            $table->boolean('push_quota_warning')->default(true);
            $table->boolean('push_student_status_change')->default(false);
            $table->boolean('push_system_updates')->default(false);

            // In-app notification preferences
            $table->boolean('in_app_payment_verified')->default(true);
            $table->boolean('in_app_payment_rejected')->default(true);
            $table->boolean('in_app_commission_earned')->default(true);
            $table->boolean('in_app_quota_warning')->default(true);
            $table->boolean('in_app_student_status_change')->default(true);
            $table->boolean('in_app_system_updates')->default(true);

            $table->timestamps();

            $table->unique('user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {
        Schema::dropIfExists('notification_preferences');
    }
};
