<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

use Illuminate\Notifications\Notifiable;

class RefCode extends Model
{
    use Notifiable;

    protected $fillable = [
        'code',
        'collaborator_id',
        'telegram_chat_id',
        'name',
        'commission_regular',
        'commission_part_time',
        'commission_distance',
    ];

    public function collaborator(): BelongsTo
    {
        return $this->belongsTo(Collaborator::class);
    }

    /**
     * Route notifications for the Telegram channel.
     */
    public function routeNotificationForTelegram(): ?string {
        return $this->telegram_chat_id;
    }

    /**
     * Check if proxy wants to receive notifications. Always true for proxy test.
     */
    public function wantsNotification(string $type, string $channel): bool {
        return $channel === 'telegram' && !empty($this->telegram_chat_id);
    }

    public static function resolveTelegramChatId(?string $sourceRef, object $defaultUser): ?string
    {
        if ($sourceRef) {
            $refCode = self::where('code', $sourceRef)->first();
            if ($refCode && $refCode->telegram_chat_id) {
                return $refCode->telegram_chat_id;
            }
        }
        return method_exists($defaultUser, 'routeNotificationForTelegram') 
            ? $defaultUser->routeNotificationForTelegram() 
            : null;
    }
}
