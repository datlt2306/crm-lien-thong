<?php

namespace App\Services;

use App\Models\PushToken;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class PushNotificationService {
    /**
     * Send push notification to a specific user.
     */
    public function sendToUser(User $user, array $notification): bool {
        $tokens = $user->pushTokens()->active()->get();

        if ($tokens->isEmpty()) {
            Log::info("No active push tokens found for user {$user->id}");
            return false;
        }

        $results = [];
        foreach ($tokens as $token) {
            $results[] = $this->sendToToken($token, $notification);
        }

        return !empty(array_filter($results));
    }

    /**
     * Send push notification to multiple users.
     */
    public function sendToUsers(array $userIds, array $notification): int {
        $successCount = 0;

        foreach ($userIds as $userId) {
            $user = User::find($userId);
            if ($user && $this->sendToUser($user, $notification)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Send push notification to all users of a specific role.
     */
    public function sendToRole(string $role, array $notification): int {
        $users = User::where('role', $role)->get();

        $successCount = 0;
        foreach ($users as $user) {
            if ($this->sendToUser($user, $notification)) {
                $successCount++;
            }
        }

        return $successCount;
    }

    /**
     * Send push notification to a specific token.
     */
    public function sendToToken(PushToken $token, array $notification): bool {
        try {
            $payload = $this->buildPayload($token->platform, $notification);

            $response = Http::withHeaders([
                'Authorization' => 'key=' . config('services.firebase.server_key'),
                'Content-Type' => 'application/json',
            ])->post(config('services.firebase.api_url'), $payload);

            if ($response->successful()) {
                $token->markAsUsed();
                Log::info("Push notification sent successfully to token {$token->id}");
                return true;
            } else {
                Log::error("Failed to send push notification to token {$token->id}: " . $response->body());

                // If token is invalid, deactivate it
                if ($this->isTokenInvalid($response->body())) {
                    $token->deactivate();
                }

                return false;
            }
        } catch (\Exception $e) {
            Log::error("Exception while sending push notification to token {$token->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Build payload for different platforms.
     */
    private function buildPayload(string $platform, array $notification): array {
        $basePayload = [
            'to' => $notification['token'],
            'notification' => [
                'title' => $notification['title'],
                'body' => $notification['body'],
                'icon' => $notification['icon'] ?? 'default',
                'sound' => $notification['sound'] ?? 'default',
            ],
            'data' => $notification['data'] ?? [],
        ];

        // Platform-specific customizations
        switch ($platform) {
            case 'ios':
                $basePayload['notification']['sound'] = $notification['sound'] ?? 'default';
                $basePayload['notification']['badge'] = $notification['badge'] ?? 1;
                break;

            case 'android':
                $basePayload['notification']['click_action'] = $notification['click_action'] ?? 'FLUTTER_NOTIFICATION_CLICK';
                break;

            case 'web':
                $basePayload['notification']['requireInteraction'] = $notification['require_interaction'] ?? true;
                break;
        }

        return $basePayload;
    }

    /**
     * Check if the response indicates an invalid token.
     */
    private function isTokenInvalid(string $responseBody): bool {
        $response = json_decode($responseBody, true);

        if (isset($response['results'][0]['error'])) {
            $error = $response['results'][0]['error'];
            return in_array($error, [
                'InvalidRegistration',
                'NotRegistered',
                'MismatchSenderId'
            ]);
        }

        return false;
    }

    /**
     * Register a new push token for a user.
     */
    public function registerToken(User $user, string $token, string $platform = 'web', ?string $deviceId = null, ?string $deviceName = null): PushToken {
        // Deactivate existing tokens for the same device
        if ($deviceId) {
            PushToken::where('user_id', $user->id)
                ->where('device_id', $deviceId)
                ->update(['is_active' => false]);
        }

        // Create or update the token
        return PushToken::updateOrCreate(
            ['token' => $token],
            [
                'user_id' => $user->id,
                'platform' => $platform,
                'device_id' => $deviceId,
                'device_name' => $deviceName,
                'is_active' => true,
                'last_used_at' => now(),
            ]
        );
    }

    /**
     * Unregister a push token.
     */
    public function unregisterToken(string $token): bool {
        return PushToken::where('token', $token)->update(['is_active' => false]) > 0;
    }

    /**
     * Get notification statistics.
     */
    public function getStats(): array {
        return [
            'total_tokens' => PushToken::count(),
            'active_tokens' => PushToken::active()->count(),
            'platforms' => PushToken::active()
                ->selectRaw('platform, count(*) as count')
                ->groupBy('platform')
                ->pluck('count', 'platform')
                ->toArray(),
        ];
    }
}
