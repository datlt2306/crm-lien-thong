<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        return DB::transaction(function () use ($amount, $description, $meta) {
            $wallet = self::where('id', $this->id)->lockForUpdate()->first();

            $balanceBefore = $wallet->balance;
            $wallet->balance += $amount;
            $wallet->total_received += $amount;
            $wallet->save();

            // Refresh model instance in memory
            $this->refresh();

            return $wallet->transactions()->create([
                'type' => 'deposit',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'meta' => $meta,
            ]);
        });
    }

    /**
     * Rút tiền từ wallet
     */
    public function withdraw(float $amount, string $description = '', array $meta = []): ?WalletTransaction {
        return DB::transaction(function () use ($amount, $description, $meta) {
            $wallet = self::where('id', $this->id)->lockForUpdate()->first();

            if ($wallet->balance < $amount) {
                return null; // Không đủ tiền
            }

            $balanceBefore = $wallet->balance;
            $wallet->balance -= $amount;
            $wallet->total_paid += $amount;
            $wallet->save();

            $this->refresh();

            return $wallet->transactions()->create([
                'type' => 'withdrawal',
                'amount' => $amount,
                'balance_before' => $balanceBefore,
                'balance_after' => $wallet->balance,
                'description' => $description,
                'meta' => $meta,
            ]);
        });
    }

    /**
     * Chuyển tiền cho wallet khác
     */
    public function transferTo(Wallet $targetWallet, float $amount, string $description = '', array $meta = []): ?WalletTransaction {
        return DB::transaction(function () use ($targetWallet, $amount, $description, $meta) {
            // Lock theo thứ tự ID để tránh deadlock
            $walletIds = [$this->id, $targetWallet->id];
            sort($walletIds);

            // Fetch locks
            self::whereIn('id', $walletIds)->lockForUpdate()->get();

            // Lấy data mới nhất sau khi lock
            $sourceWallet = self::find($this->id);
            $destinationWallet = self::find($targetWallet->id);

            if ($sourceWallet->balance < $amount) {
                Log::warning('Wallet transfer failed: insufficient funds', [
                    'from_wallet_id' => $sourceWallet->id,
                    'to_wallet_id' => $destinationWallet->id,
                    'amount' => $amount,
                    'balance' => $sourceWallet->balance,
                ]);
                return null; // Không đủ tiền
            }

            $sourceBalanceBefore = $sourceWallet->balance;
            $sourceWallet->balance -= $amount;
            $sourceWallet->total_paid += $amount;
            $sourceWallet->save();

            // Tạo transaction cho wallet gửi
            $outTransaction = $sourceWallet->transactions()->create([
                'type' => 'transfer_out',
                'amount' => $amount,
                'balance_before' => $sourceBalanceBefore,
                'balance_after' => $sourceWallet->balance,
                'description' => $description,
                'meta' => $meta,
                'related_wallet_id' => $destinationWallet->id,
            ]);

            // Cập nhật wallet nhận
            $targetBalanceBefore = $destinationWallet->balance;
            $destinationWallet->balance += $amount;
            $destinationWallet->total_received += $amount;
            $destinationWallet->save();

            // Tạo transaction cho wallet nhận
            $destinationWallet->transactions()->create([
                'type' => 'transfer_in',
                'amount' => $amount,
                'balance_before' => $targetBalanceBefore,
                'balance_after' => $destinationWallet->balance,
                'description' => "Nhận từ {$sourceWallet->collaborator->full_name}",
                'meta' => $meta,
                'related_wallet_id' => $sourceWallet->id,
            ]);

            $this->refresh();
            $targetWallet->refresh();

            Log::info('Wallet transfer success', [
                'from_wallet_id' => $sourceWallet->id,
                'to_wallet_id' => $destinationWallet->id,
                'amount' => $amount,
                'from_balance_after' => $sourceWallet->balance,
                'to_balance_after' => $destinationWallet->balance,
            ]);

            return $outTransaction;
        });
    }
}
