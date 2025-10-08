<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\CommissionItem;
use App\Services\DashboardCacheService;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;

class AccountantFinancialSummary extends BaseWidget {
    use WithDashboardFilters;
    protected ?string $pollingInterval = '60s';

    protected function getCards(): array {
        try {
            $filters = $this->filters;

            $stats = DashboardCacheService::remember('accountant:financial_summary', $filters, DashboardCacheService::DEFAULT_TTL_SECONDS, function () use ($filters) {
                return $this->calculateFinancialSummary($filters);
            });

            return [
                Stat::make('Tổng doanh thu', $stats['total_revenue'])->description('VND')->color('success'),
                Stat::make('Tổng hoa hồng đã chi', $stats['total_commission_paid'])->description('VND')->color('warning'),
                Stat::make('Lợi nhuận ròng', $stats['net_profit'])->description('VND')->color('info'),
                Stat::make('Tỷ lệ hoa hồng', $stats['commission_rate'])->description('Hoa hồng / Doanh thu')->color('gray'),
            ];
        } catch (\Exception $e) {
            // Fallback khi có lỗi
            return [
                Stat::make('Lỗi tải dữ liệu', 'Không thể tải thống kê tài chính')->description('Vui lòng thử lại sau')->color('danger'),
            ];
        }
    }

    protected function calculateFinancialSummary(array $filters): array {
        [$from, $to] = $this->getRangeBounds($filters);

        // Tổng doanh thu trong khoảng thời gian
        $totalRevenue = Payment::where('status', 'verified')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        // Tổng hoa hồng đã chi trong khoảng thời gian
        $totalCommissionPaid = CommissionItem::where('status', 'paid')
            ->whereBetween('updated_at', [$from, $to])
            ->sum('amount');

        // Lợi nhuận ròng
        $netProfit = $totalRevenue - $totalCommissionPaid;

        // Tỷ lệ hoa hồng
        $commissionRate = $totalRevenue > 0 ? round(($totalCommissionPaid / $totalRevenue) * 100, 1) : 0;

        return [
            'total_revenue' => number_format($totalRevenue) . ' VND',
            'total_commission_paid' => number_format($totalCommissionPaid) . ' VND',
            'net_profit' => number_format($netProfit) . ' VND',
            'commission_rate' => $commissionRate . '%',
        ];
    }

    protected function getRangeBounds(array $filters): array {
        $tz = DashboardCacheService::getTimezone();
        $now = CarbonImmutable::now($tz);
        switch ($filters['range'] ?? 'last_30_days') {
            case 'today':
                return [$now->startOfDay(), $now->endOfDay()];
            case 'last_7_days':
                return [$now->subDays(6)->startOfDay(), $now->endOfDay()];
            case 'this_month':
                return [$now->startOfMonth(), $now->endOfMonth()];
            case 'custom':
                $from = $filters['from'] ? CarbonImmutable::parse($filters['from'], $tz)->startOfDay() : $now->subDays(29)->startOfDay();
                $to = $filters['to'] ? CarbonImmutable::parse($filters['to'], $tz)->endOfDay() : $now->endOfDay();
                return [$from, $to];
            case 'last_30_days':
            default:
                return [$now->subDays(29)->startOfDay(), $now->endOfDay()];
        }
    }
}
