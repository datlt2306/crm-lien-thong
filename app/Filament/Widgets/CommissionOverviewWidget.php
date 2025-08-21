<?php

namespace App\Filament\Widgets;

use App\Models\CommissionItem;
use App\Models\Wallet;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\Auth;

class CommissionOverviewWidget extends BaseWidget {
    protected function getStats(): array {
        $user = Auth::user();
        $stats = [];

        if ($user->role === 'super_admin') {
            // Góc nhìn Organization
            $stats = $this->getOrganizationStats();
        } elseif ($user->role === 'user') {
            // Góc nhìn CTV
            $stats = $this->getCollaboratorStats($user);
        }

        return $stats;
    }

    private function getOrganizationStats(): array {
        return [
            Stat::make('Tổng đã chi cho CTV cấp 1', function () {
                return CommissionItem::where('role', 'direct')
                    ->where('status', CommissionItem::STATUS_PAID)
                    ->sum('amount');
            })
                ->description('Tổng tiền đã thanh toán cho CTV cấp 1')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success')
                ->chart([7, 2, 10, 3, 15, 4, 17]),

            Stat::make('Commission đang chờ', function () {
                return CommissionItem::where('role', 'direct')
                    ->whereIn('status', [CommissionItem::STATUS_PAYABLE, CommissionItem::STATUS_PENDING])
                    ->sum('amount');
            })
                ->description('Commission chưa thanh toán')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Tổng commission đã tạo', function () {
                return CommissionItem::where('role', 'direct')->sum('amount');
            })
                ->description('Tổng commission đã tạo cho CTV cấp 1')
                ->descriptionIcon('heroicon-m-document-text')
                ->color('info'),
        ];
    }

    private function getCollaboratorStats($user): array {
        // Tìm collaborator của user
        $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();

        if (!$collaborator) {
            return [
                Stat::make('Không tìm thấy thông tin CTV', 'N/A')
                    ->description('Vui lòng liên hệ admin')
                    ->color('danger'),
            ];
        }

        $wallet = Wallet::where('collaborator_id', $collaborator->id)->first();

        if ($collaborator->isLevel1()) {
            // CTV cấp 1
            return [
                Stat::make('Số dư ví', function () use ($wallet) {
                    return $wallet ? number_format($wallet->balance) : '0';
                })
                    ->description('Số dư hiện tại trong ví')
                    ->descriptionIcon('heroicon-m-wallet')
                    ->color('success'),

                Stat::make('Tổng nhận từ Org', function () use ($wallet) {
                    return $wallet ? number_format($wallet->total_received) : '0';
                })
                    ->description('Tổng tiền đã nhận từ Organization')
                    ->descriptionIcon('heroicon-m-arrow-down')
                    ->color('info'),

                Stat::make('Tổng đã chi cho tuyến dưới', function () use ($wallet) {
                    return $wallet ? number_format($wallet->total_paid) : '0';
                })
                    ->description('Tổng tiền đã chi cho CTV cấp 2')
                    ->descriptionIcon('heroicon-m-arrow-up')
                    ->color('warning'),

                Stat::make('Net còn lại', function () use ($wallet) {
                    if (!$wallet) return '0';
                    $net = $wallet->total_received - $wallet->total_paid;
                    return number_format($net);
                })
                    ->description('Số tiền thực tế còn lại')
                    ->descriptionIcon('heroicon-m-calculator')
                    ->color('success'),
            ];
        } else {
            // CTV cấp 2
            return [
                Stat::make('Tổng được hưởng', function () use ($collaborator) {
                    return number_format(
                        CommissionItem::where('recipient_collaborator_id', $collaborator->id)
                            ->sum('amount')
                    );
                })
                    ->description('Tổng commission được hưởng')
                    ->descriptionIcon('heroicon-m-gift')
                    ->color('success'),

                Stat::make('Đã thanh toán', function () use ($collaborator) {
                    return number_format(
                        CommissionItem::where('recipient_collaborator_id', $collaborator->id)
                            ->where('status', CommissionItem::STATUS_PAID)
                            ->sum('amount')
                    );
                })
                    ->description('Commission đã được thanh toán')
                    ->descriptionIcon('heroicon-m-check-circle')
                    ->color('success'),

                Stat::make('Đang chờ', function () use ($collaborator) {
                    return number_format(
                        CommissionItem::where('recipient_collaborator_id', $collaborator->id)
                            ->whereIn('status', [CommissionItem::STATUS_PAYABLE, CommissionItem::STATUS_PENDING])
                            ->sum('amount')
                    );
                })
                    ->description('Commission đang chờ thanh toán')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),

                Stat::make('Số dư ví', function () use ($wallet) {
                    return $wallet ? number_format($wallet->balance) : '0';
                })
                    ->description('Số dư hiện tại trong ví')
                    ->descriptionIcon('heroicon-m-wallet')
                    ->color('info'),
            ];
        }
    }
}
