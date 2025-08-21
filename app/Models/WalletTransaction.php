<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
        'amount' => 'decimal:2',
        'balance_before' => 'decimal:2',
        'balance_after' => 'decimal:2',
        'meta' => 'array',
    ];

    /**
     * Quan hệ: Wallet chứa transaction
     */
    public function wallet(): BelongsTo {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Quan hệ: Wallet liên quan (cho transfer)
     */
    public function relatedWallet(): BelongsTo {
        return $this->belongsTo(Wallet::class, 'related_wallet_id');
    }

    /**
     * Quan hệ: Commission item liên quan
     */
    public function commissionItem(): BelongsTo {
        return $this->belongsTo(CommissionItem::class);
    }

    /**
     * Lấy collaborator từ wallet
     */
    public function getCollaboratorAttribute() {
        return $this->wallet->collaborator;
    }
}
