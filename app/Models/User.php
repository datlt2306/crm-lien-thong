<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use App\Traits\HasAuditLog;

use Filament\Models\Contracts\FilamentUser;
use Filament\Models\Contracts\HasAvatar;
use Filament\Models\Contracts\HasName;
use Filament\Panel;

use Illuminate\Support\Facades\Storage;

class User extends Authenticatable implements FilamentUser, HasAvatar, HasName {
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, HasAuditLog;

    public function canAccessPanel(Panel $panel): bool
    {
        return $this->is_active;
    }

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
        'bio',
        'password',
        'role',
        'is_active',
        'telegram_chat_id',
        'google_id',
        'google_token',
        'google_refresh_token',
        'google_avatar',
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
        return $this->notificationPreferences()->firstOrCreate([]);
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
            'telegram' => $preferences->wantsTelegramFor($type),
            default => false,
        };
    }

    /**
     * Route notifications for the Telegram channel.
     */
    public function routeNotificationForTelegram(): ?string {
        return $this->telegram_chat_id;
    }

    public function collaborator() {
        return $this->hasOne(Collaborator::class, 'email', 'email');
    }

    /**
     * Lấy ảnh đại diện cho Filament
     */
    public function getFilamentAvatarUrl(): ?string
    {
        if ($this->google_avatar && str_starts_with($this->google_avatar, 'http')) {
            return $this->google_avatar;
        }

        if ($this->avatar && Storage::disk('public')->exists($this->avatar)) {
            return Storage::url($this->avatar);
        }

        return null;
    }

    /**
     * Lấy tên hiển thị cho Filament
     */
    public function getFilamentName(): string
    {
        return $this->name;
    }
}
