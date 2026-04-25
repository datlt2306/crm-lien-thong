<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RefCode extends Model
{
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
