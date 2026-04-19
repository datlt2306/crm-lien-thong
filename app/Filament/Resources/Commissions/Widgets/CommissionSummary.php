<?php

namespace App\Filament\Resources\Commissions\Widgets;

use App\Models\CommissionItem;
use App\Models\Collaborator;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CommissionSummary extends BaseWidget {
    protected function getStats(): array {
        $user = Auth::user();
        $query = CommissionItem::query();

        // Nếu là CTV, chỉ thống kê tiền của chính mình
        if ($user->role === 'ctv') {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                $query->where('recipient_collaborator_id', $collaborator->id);
            } else {
                return [];
            }
        }

        $pending = (clone $query)->where('status', CommissionItem::STATUS_PENDING)->sum('amount');
        $payable = (clone $query)->whereIn('status', [CommissionItem::STATUS_PAYABLE, CommissionItem::STATUS_PAYMENT_CONFIRMED])->sum('amount');
        $paid = (clone $query)->where('status', CommissionItem::STATUS_RECEIVED_CONFIRMED)->sum('amount');

        return [
            Stat::make('Hoa hồng chờ (SV chưa nhập học)', number_format($pending) . ' đ')
                ->description('Tiền treo, sẽ mở khóa khi SV nhập học')
                ->descriptionIcon('heroicon-m-clock')
                ->color('gray'),
            Stat::make('Hoa hồng cần chi trả', number_format($payable) . ' đ')
                ->description('Tiền đã đủ điều kiện, đang chờ thanh toán')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning'),
            Stat::make('Hoa hồng đã quyết toán', number_format($paid) . ' đ')
                ->description('Tiền đã về tới ví của CTV')
                ->descriptionIcon('heroicon-m-check-circle')
                ->color('success'),
        ];
    }
}
