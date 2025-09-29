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
        // Push notification preferences
        'push_payment_verified',
        'push_payment_rejected',
        'push_commission_earned',
        'push_quota_warning',
        'push_student_status_change',
        'push_system_updates',
        // In-app notification preferences
        'in_app_payment_verified',
        'in_app_payment_rejected',
        'in_app_commission_earned',
        'in_app_quota_warning',
        'in_app_student_status_change',
        'in_app_system_updates',
    ];

    protected $casts = [
        // Email preferences
        'email_payment_verified' => 'boolean',
        'email_payment_rejected' => 'boolean',
        'email_commission_earned' => 'boolean',
        'email_quota_warning' => 'boolean',
        'email_student_status_change' => 'boolean',
        'email_system_updates' => 'boolean',
        // Push notification preferences
        'push_payment_verified' => 'boolean',
        'push_payment_rejected' => 'boolean',
        'push_commission_earned' => 'boolean',
        'push_quota_warning' => 'boolean',
        'push_student_status_change' => 'boolean',
        'push_system_updates' => 'boolean',
        // In-app notification preferences
        'in_app_payment_verified' => 'boolean',
        'in_app_payment_rejected' => 'boolean',
        'in_app_commission_earned' => 'boolean',
        'in_app_quota_warning' => 'boolean',
        'in_app_student_status_change' => 'boolean',
        'in_app_system_updates' => 'boolean',
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
     * Get all enabled notification types for email.
     */
    public function getEnabledEmailTypes(): array {
        $types = [];
        foreach (['payment_verified', 'payment_rejected', 'commission_earned', 'quota_warning', 'student_status_change', 'system_updates'] as $type) {
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
        foreach (['payment_verified', 'payment_rejected', 'commission_earned', 'quota_warning', 'student_status_change', 'system_updates'] as $type) {
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
        foreach (['payment_verified', 'payment_rejected', 'commission_earned', 'quota_warning', 'student_status_change', 'system_updates'] as $type) {
            if ($this->wantsInAppFor($type)) {
                $types[] = $type;
            }
        }
        return $types;
    }
}
