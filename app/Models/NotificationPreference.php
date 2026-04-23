<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NotificationPreference extends Model {
    use HasFactory;

    protected $fillable = [
        'user_id',
        // Email preferences
        'email_payment_verified',
        'email_payment_rejected',
        'email_commission_earned',
        'email_quota_warning',
        'email_student_status_change',
        'email_system_updates',
        'email_student_registered',
        'email_payment_bill_uploaded',

        // Push notification preferences
        'push_payment_verified',
        'push_payment_rejected',
        'push_commission_earned',
        'push_quota_warning',
        'push_student_status_change',
        'push_system_updates',
        'push_student_registered',
        'push_payment_bill_uploaded',

        // In-app notification preferences
        'in_app_payment_verified',
        'in_app_payment_rejected',
        'in_app_commission_earned',
        'in_app_quota_warning',
        'in_app_student_status_change',
        'in_app_system_updates',
        'in_app_student_registered',
        'in_app_payment_bill_uploaded',

        // Telegram preferences
        'telegram_payment_verified',
        'telegram_payment_rejected',
        'telegram_commission_earned',
        'telegram_quota_warning',
        'telegram_student_status_change',
        'telegram_system_updates',
        'telegram_student_registered',
        'telegram_payment_bill_uploaded',
    ];

    protected $casts = [
        // Email preferences
        'email_payment_verified' => 'boolean',
        'email_payment_rejected' => 'boolean',
        'email_commission_earned' => 'boolean',
        'email_quota_warning' => 'boolean',
        'email_student_status_change' => 'boolean',
        'email_system_updates' => 'boolean',
        'email_student_registered' => 'boolean',
        'email_payment_bill_uploaded' => 'boolean',

        // Push notification preferences
        'push_payment_verified' => 'boolean',
        'push_payment_rejected' => 'boolean',
        'push_commission_earned' => 'boolean',
        'push_quota_warning' => 'boolean',
        'push_student_status_change' => 'boolean',
        'push_system_updates' => 'boolean',
        'push_student_registered' => 'boolean',
        'push_payment_bill_uploaded' => 'boolean',

        // In-app notification preferences
        'in_app_payment_verified' => 'boolean',
        'in_app_payment_rejected' => 'boolean',
        'in_app_commission_earned' => 'boolean',
        'in_app_quota_warning' => 'boolean',
        'in_app_student_status_change' => 'boolean',
        'in_app_system_updates' => 'boolean',
        'in_app_student_registered' => 'boolean',
        'in_app_payment_bill_uploaded' => 'boolean',

        // Telegram preferences
        'telegram_payment_verified' => 'boolean',
        'telegram_payment_rejected' => 'boolean',
        'telegram_commission_earned' => 'boolean',
        'telegram_quota_warning' => 'boolean',
        'telegram_student_status_change' => 'boolean',
        'telegram_system_updates' => 'boolean',
        'telegram_student_registered' => 'boolean',
        'telegram_payment_bill_uploaded' => 'boolean',
    ];

    /**
     * Get the user that owns the notification preferences.
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    /**
     * Check if user wants to receive email notifications for a specific type.
     */
    public function wantsEmailFor(string $type): bool {
        $emailField = 'email_' . $type;
        return $this->$emailField ?? false;
    }

    /**
     * Check if user wants to receive push notifications for a specific type.
     */
    public function wantsPushFor(string $type): bool {
        $pushField = 'push_' . $type;
        return $this->$pushField ?? false;
    }

    /**
     * Check if user wants to receive in-app notifications for a specific type.
     */
    public function wantsInAppFor(string $type): bool {
        $inAppField = 'in_app_' . $type;
        return $this->$inAppField ?? false;
    }

    /**
     * Check if user wants to receive telegram notifications for a specific type.
     */
    public function wantsTelegramFor(string $type): bool {
        $telegramField = 'telegram_' . $type;
        return $this->$telegramField ?? false;
    }

    /**
     * Get all supported notification types.
     */
    public static function getSupportedTypes(): array {
        return [
            'student_registered',
            'payment_bill_uploaded',
            'payment_verified',
            'payment_rejected',
            'commission_earned',
            'quota_warning',
            'student_status_change',
            'system_updates',
        ];
    }

    /**
     * Get all enabled notification types for email.
     */
    public function getEnabledEmailTypes(): array {
        $types = [];
        foreach (self::getSupportedTypes() as $type) {
            if ($this->wantsEmailFor($type)) {
                $types[] = $type;
            }
        }
        return $types;
    }

    /**
     * Get all enabled notification types for push.
     */
    public function getEnabledPushTypes(): array {
        $types = [];
        foreach (self::getSupportedTypes() as $type) {
            if ($this->wantsPushFor($type)) {
                $types[] = $type;
            }
        }
        return $types;
    }

    /**
     * Get all enabled notification types for in-app.
     */
    public function getEnabledInAppTypes(): array {
        $types = [];
        foreach (self::getSupportedTypes() as $type) {
            if ($this->wantsInAppFor($type)) {
                $types[] = $type;
            }
        }
        return $types;
    }

    /**
     * Get all enabled notification types for telegram.
     */
    public function getEnabledTelegramTypes(): array {
        $types = [];
        foreach (self::getSupportedTypes() as $type) {
            if ($this->wantsTelegramFor($type)) {
                $types[] = $type;
            }
        }
        return $types;
    }
}
