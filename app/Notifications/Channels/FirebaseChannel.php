<?php

namespace App\Notifications\Channels;

use App\Services\PushNotificationService;
use Illuminate\Notifications\Notification;

class FirebaseChannel {
    public function __construct(
        private PushNotificationService $pushService
    ) {
    }

    /**
     * Send the given notification.
     */
    public function send($notifiable, Notification $notification): void {
        if (!method_exists($notification, 'toFirebase')) {
            return;
        }

        $firebaseNotification = $notification->toFirebase($notifiable);

        if (!$firebaseNotification) {
            return;
        }

        // Add token to notification data
        $tokens = $notifiable->pushTokens()->active()->pluck('token')->toArray();

        foreach ($tokens as $token) {
            $firebaseNotification['token'] = $token;
            $this->pushService->sendToToken(
                $notifiable->pushTokens()->where('token', $token)->first(),
                $firebaseNotification
            );
        }
    }
}
