<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushToken extends Model {
    use HasFactory;

    protected $fillable = [
        'user_id',
        'token',
        'platform',
        'device_id',
        'device_name',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];

    /**
     * Get the user that owns the push token.
     */
    public function user(): BelongsTo {
        return $this->belongsTo(User::class);
    }

    /**
     * Scope a query to only include active tokens.
     */
    public function scopeActive($query) {
        return $query->where('is_active', true);
    }

    /**
     * Scope a query to only include tokens for a specific platform.
     */
    public function scopeForPlatform($query, string $platform) {
        return $query->where('platform', $platform);
    }

    /**
     * Mark token as used.
     */
    public function markAsUsed(): void {
        $this->update(['last_used_at' => now()]);
    }

    /**
     * Deactivate token.
     */
    public function deactivate(): void {
        $this->update(['is_active' => false]);
    }

    /**
     * Activate token.
     */
    public function activate(): void {
        $this->update(['is_active' => true]);
    }
}
