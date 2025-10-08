<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable {
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'phone',
        'avatar',
        'password',
        'role',
        'organization_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }


    /**
     * Get the notification preferences for the user.
     */
    public function notificationPreferences(): HasOne {
        return $this->hasOne(NotificationPreference::class);
    }

    /**
     * Get or create notification preferences for the user.
     */
    public function getNotificationPreferences(): NotificationPreference {
        return $this->notificationPreferences ?? $this->notificationPreferences()->create([]);
    }

    /**
     * Check if user wants to receive notifications for a specific type and channel.
     */
    public function wantsNotification(string $type, string $channel = 'in_app'): bool {
        $preferences = $this->getNotificationPreferences();

        return match ($channel) {
            'email' => $preferences->wantsEmailFor($type),
            'push' => $preferences->wantsPushFor($type),
            'in_app' => $preferences->wantsInAppFor($type),
            default => false,
        };
    }

    /**
     * Quan hệ: User có thể có Collaborator record (nếu role là 'ctv')
     */
    public function collaborator() {
        return $this->hasOne(Collaborator::class, 'email', 'email');
    }

    /**
     * Quan hệ: User có thể là owner của Organization (nếu role là 'organization_owner')
     */
    public function ownedOrganization() {
        return $this->hasOne(Organization::class, 'organization_owner_id');
    }

    /**
     * Quan hệ: User thuộc về Organization (khi lưu trực tiếp organization_id)
     */
    public function organization() {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Lấy organization mà user thuộc về
     */
    public function getOrganization() {
        if (!empty($this->organization_id) && $this->organization) {
            return $this->organization;
        }
        if ($this->role === 'organization_owner') {
            return $this->ownedOrganization;
        }

        if ($this->role === 'ctv' && $this->collaborator) {
            return $this->collaborator->organization;
        }

        return null;
    }
}
