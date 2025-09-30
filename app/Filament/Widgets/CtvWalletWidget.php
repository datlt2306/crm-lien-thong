<?php

namespace App\Filament\Widgets;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Services\DashboardCacheService;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CtvWalletWidget extends BaseWidget {
    use WithDashboardFilters;
    protected ?string $pollingInterval = '60s';

    protected function getCards(): array {
        $userId = Auth::id();
        
        $walletData = DashboardCacheService::remember("ctv:wallet:{$userId}", [], DashboardCacheService::DEFAULT_TTL_SECONDS, function () use ($userId) {
            return $this->getWalletData($userId);
        });

        return [
            Stat::make('Số dư ví', $walletData['balance'])->description('VND')->color('success'),
            Stat::make('Tổng nạp', $walletData['total_deposit'])->description('VND')->color('info'),
            Stat::make('Tổng rút', $walletData['total_withdraw'])->description('VND')->color('warning'),
            Stat::make('Giao dịch gần nhất', $walletData['last_transaction'])->description($walletData['last_transaction_date'])->color('gray'),
        ];
    }

    protected function getWalletData(int $userId): array {
        $wallet = Wallet::where('user_id', $userId)->first();
        
        if (!$wallet) {
            return [
                'balance' => '0 VND',
                'total_deposit' => '0 VND',
                'total_withdraw' => '0 VND',
                'last_transaction' => 'Chưa có giao dịch',
                'last_transaction_date' => '',
            ];
        }

        $totalDeposit = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'deposit')
            ->sum('amount');

        $totalWithdraw = WalletTransaction::where('wallet_id', $wallet->id)
            ->where('type', 'withdraw')
            ->sum('amount');

        $lastTransaction = WalletTransaction::where('wallet_id', $wallet->id)
            ->latest()
            ->first();

        return [
            'balance' => number_format($wallet->balance) . ' VND',
            'total_deposit' => number_format($totalDeposit) . ' VND',
            'total_withdraw' => number_format($totalWithdraw) . ' VND',
            'last_transaction' => $lastTransaction ? 
                ($lastTransaction->type === 'deposit' ? '+' : '-') . number_format($lastTransaction->amount) . ' VND' : 
                'Chưa có giao dịch',
            'last_transaction_date' => $lastTransaction ? $lastTransaction->created_at->format('d/m/Y H:i') : '',
        ];
    }
}
