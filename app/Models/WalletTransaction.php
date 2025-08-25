<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WalletTransaction extends Model {
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
        'meta' => 'array',
    ];
}
