<?php

namespace App\Filament\Widgets;

use App\Models\CommissionItem;
use App\Models\Wallet;
use App\Models\Collaborator;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class CommissionOverviewWidget extends BaseWidget {
    protected function getStats(): array {
        $user = \Illuminate\Support\Facades\Auth::user();
        $stats = [];

        if ($user->role === 'super_admin') {
            // Góc nhìn Organization
            $stats = [
                Stat::make(
                    'Tổng đã chi cho CTV cấp 1',
                    number_format(CommissionItem::where('role', 'PRIMARY')
                        ->where('status', CommissionItem::STATUS_PAID)
                        ->sum('amount')) . ' VNĐ'
                )
                    ->description('Tổng hoa hồng đã thanh toán cho CTV cấp 1')
                    ->descriptionIcon('heroicon-m-currency-dollar')
                    ->color('success'),

                Stat::make(
                    'Commission đang chờ',
                    CommissionItem::where('status', CommissionItem::STATUS_PENDING)->count()
                )
                    ->description('Số commission chờ nhập học')
                    ->descriptionIcon('heroicon-m-clock')
                    ->color('warning'),

                Stat::make(
                    'Tổng commission đã tạo',
                    CommissionItem::count()
                )
                    ->description('Tổng số commission trong hệ thống')
                    ->descriptionIcon('heroicon-m-document-text')
                    ->color('info'),
            ];
        } elseif ($user->role === 'ctv') {
            // Góc nhìn CTV
            $collaborator = Collaborator::where('email', $user->email)->first();

            if ($collaborator) {
                $wallet = Wallet::where('collaborator_id', $collaborator->id)->first();

                if ($collaborator->isLevel1()) {
                    // CTV cấp 1
                    $stats = [
                        Stat::make(
                            'Số dư ví',
                            number_format($wallet ? $wallet->balance : 0) . ' VNĐ'
                        )
                            ->description('Số dư hiện tại trong ví')
                            ->descriptionIcon('heroicon-m-wallet')
                            ->color('success'),

                        Stat::make(
                            'Tổng nhận từ Org',
                            number_format($wallet ? $wallet->total_received : 0) . ' VNĐ'
                        )
                            ->description('Tổng tiền đã nhận từ tổ chức')
                            ->descriptionIcon('heroicon-m-arrow-down')
                            ->color('info'),

                        Stat::make(
                            'Tổng chi cho tuyến dưới',
                            number_format($wallet ? $wallet->total_paid : 0) . ' VNĐ'
                        )
                            ->description('Tổng tiền đã chi cho CTV cấp 2')
                            ->descriptionIcon('heroicon-m-arrow-up')
                            ->color('warning'),

                        Stat::make(
                            'Net còn lại',
                            number_format($wallet ? ($wallet->balance - $wallet->total_paid) : 0) . ' VNĐ'
                        )
                            ->description('Số dư thực tế có thể sử dụng')
                            ->descriptionIcon('heroicon-m-calculator')
                            ->color('success'),
                    ];
                } else {
                    // CTV cấp 2
                    $stats = [
                        Stat::make(
                            'Tổng được hưởng',
                            number_format(CommissionItem::where('recipient_id', $collaborator->id)
                                ->whereIn('status', [CommissionItem::STATUS_PAID, CommissionItem::STATUS_PAYABLE])
                                ->sum('amount')) . ' VNĐ'
                        )
                            ->description('Tổng hoa hồng đã được hưởng')
                            ->descriptionIcon('heroicon-m-gift')
                            ->color('success'),

                        Stat::make(
                            'Đã thanh toán',
                            number_format(CommissionItem::where('recipient_id', $collaborator->id)
                                ->where('status', CommissionItem::STATUS_PAID)
                                ->sum('amount')) . ' VNĐ'
                        )
                            ->description('Hoa hồng đã được thanh toán')
                            ->descriptionIcon('heroicon-m-check-circle')
                            ->color('success'),

                        Stat::make(
                            'Đang chờ',
                            number_format(CommissionItem::where('recipient_id', $collaborator->id)
                                ->where('status', CommissionItem::STATUS_PENDING)
                                ->sum('amount')) . ' VNĐ'
                        )
                            ->description('Hoa hồng chờ nhập học')
                            ->descriptionIcon('heroicon-m-clock')
                            ->color('warning'),

                        Stat::make(
                            'Số dư ví',
                            number_format($wallet ? $wallet->balance : 0) . ' VNĐ'
                        )
                            ->description('Số dư hiện tại trong ví')
                            ->descriptionIcon('heroicon-m-wallet')
                            ->color('info'),
                    ];
                }
            }
        } elseif ($user->role === 'chủ đơn vị') {
            // Góc nhìn Chủ đơn vị
            $org = \App\Models\Organization::where('owner_id', $user->id)->first();

            if ($org) {
                $stats = [
                    Stat::make(
                        'Tổng commission tổ chức',
                        number_format(CommissionItem::whereHas('recipient', function ($query) use ($org) {
                            $query->where('organization_id', $org->id);
                        })->sum('amount')) . ' VNĐ'
                    )
                        ->description('Tổng hoa hồng của tổ chức')
                        ->descriptionIcon('heroicon-m-building-office')
                        ->color('info'),

                    Stat::make(
                        'Commission đã thanh toán',
                        number_format(CommissionItem::whereHas('recipient', function ($query) use ($org) {
                            $query->where('organization_id', $org->id);
                        })->where('status', CommissionItem::STATUS_PAID)->sum('amount')) . ' VNĐ'
                    )
                        ->description('Hoa hồng đã thanh toán')
                        ->descriptionIcon('heroicon-m-check-circle')
                        ->color('success'),

                    Stat::make(
                        'Commission đang chờ',
                        CommissionItem::whereHas('recipient', function ($query) use ($org) {
                            $query->where('organization_id', $org->id);
                        })->where('status', CommissionItem::STATUS_PENDING)->count()
                    )
                        ->description('Commission chờ nhập học')
                        ->descriptionIcon('heroicon-m-clock')
                        ->color('warning'),
                ];
            }
        }

        return $stats;
    }
}
