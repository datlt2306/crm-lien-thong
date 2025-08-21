<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Wallet extends Model {
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

    /**
     * Quan hệ: Collaborator sở hữu wallet
     */
    public function collaborator(): BelongsTo {
        return $this->belongsTo(Collaborator::class);
    }

    /**
     * Quan hệ: Các giao dịch trong wallet
     */
    public function transactions(): HasMany {
        return $this->hasMany(WalletTransaction::class);
    }

    /**
     * Nạp tiền vào wallet
     */
    public function deposit(float $amount, string $description = '', array $meta = []): WalletTransaction {
        $balanceBefore = $this->balance;
        $this->balance += $amount;
        $this->total_received += $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => 'deposit',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description,
            'meta' => $meta,
        ]);
    }

    /**
     * Rút tiền từ wallet
     */
    public function withdraw(float $amount, string $description = '', array $meta = []): ?WalletTransaction {
        if ($this->balance < $amount) {
            return null; // Không đủ tiền
        }

        $balanceBefore = $this->balance;
        $this->balance -= $amount;
        $this->total_paid += $amount;
        $this->save();

        return $this->transactions()->create([
            'type' => 'withdrawal',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description,
            'meta' => $meta,
        ]);
    }

    /**
     * Chuyển tiền cho wallet khác
     */
    public function transferTo(Wallet $targetWallet, float $amount, string $description = '', array $meta = []): ?WalletTransaction {
        if ($this->balance < $amount) {
            return null; // Không đủ tiền
        }

        $balanceBefore = $this->balance;
        $this->balance -= $amount;
        $this->total_paid += $amount;
        $this->save();

        // Tạo transaction cho wallet gửi
        $outTransaction = $this->transactions()->create([
            'type' => 'transfer_out',
            'amount' => $amount,
            'balance_before' => $balanceBefore,
            'balance_after' => $this->balance,
            'description' => $description,
            'meta' => $meta,
            'related_wallet_id' => $targetWallet->id,
        ]);

        // Cập nhật wallet nhận
        $targetBalanceBefore = $targetWallet->balance;
        $targetWallet->balance += $amount;
        $targetWallet->total_received += $amount;
        $targetWallet->save();

        // Tạo transaction cho wallet nhận
        $targetWallet->transactions()->create([
            'type' => 'transfer_in',
            'amount' => $amount,
            'balance_before' => $targetBalanceBefore,
            'balance_after' => $targetWallet->balance,
            'description' => "Nhận từ {$this->collaborator->full_name}",
            'meta' => $meta,
            'related_wallet_id' => $this->id,
        ]);

        return $outTransaction;
    }
}
