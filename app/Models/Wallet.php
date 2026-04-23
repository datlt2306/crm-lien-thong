<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model {
    use HasFactory;

    protected $fillable = [
        'collaborator_id',
        'balance',
        'total_received',
        'total_paid',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_received' => 'decimal:2',
        'total_paid' => 'decimal:2',
    ];

    public function collaborator(): BelongsTo {
        return $this->belongsTo(Collaborator::class);
    }

    public function transactions(): HasMany {
        return $this->hasMany(WalletTransaction::class);
    }
}
