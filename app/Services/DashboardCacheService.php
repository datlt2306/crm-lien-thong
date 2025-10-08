<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;

class DashboardCacheService {
    public const DEFAULT_TTL_SECONDS = 120; // {TTL}s

    public static function getTimezone(): string {
        return config('app.timezone', 'Asia/Ho_Chi_Minh'); // {TZ}
    }

    protected static function getVersionKey(): string {
        return 'dash:version';
    }

    public static function bumpVersion(): void {
        Cache::increment(self::getVersionKey());
    }

    protected static function getVersion(): int {
        $version = Cache::get(self::getVersionKey());
        if (!$version) {
            Cache::forever(self::getVersionKey(), 1);
            return 1;
        }
        return (int) $version;
    }

    public static function remember(string $prefix, array $filters, int $ttlSeconds, Closure $callback) {
        // Tạm thời tắt cache để tránh timeout
        return $callback();
    }
}
