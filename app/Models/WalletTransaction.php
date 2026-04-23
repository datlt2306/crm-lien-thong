<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletTransaction extends Model {
    use HasFactory;

    protected $fillable = [
        'wallet_id',
        'type',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'meta',
        'related_wallet_id',
        'commission_item_id',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'meta' => 'array',
    ];

    public function wallet(): BelongsTo {
        return $this->belongsTo(Wallet::class);
    }

    public function relatedWallet(): BelongsTo {
        return $this->belongsTo(Wallet::class, 'related_wallet_id');
    }

    public function commissionItem(): BelongsTo {
        return $this->belongsTo(CommissionItem::class);
    }
}
