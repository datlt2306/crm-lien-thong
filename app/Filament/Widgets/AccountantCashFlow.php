<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\CommissionItem;
use App\Services\DashboardCacheService;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class AccountantCashFlow extends ChartWidget {
    use WithDashboardFilters;
    protected ?string $heading = 'Dòng tiền theo ngày';
    protected ?string $pollingInterval = '60s';

    protected function getData(): array {
        try {
            $filters = $this->filters;
            $data = DashboardCacheService::remember('accountant:cash_flow', $filters, DashboardCacheService::DEFAULT_TTL_SECONDS, function () use ($filters) {
                return $this->buildCashFlowSeries($filters);
            });
            return $data;
        } catch (\Exception $e) {
            // Fallback khi có lỗi
            return [
                'datasets' => [],
                'labels' => ['Lỗi tải dữ liệu'],
            ];
        }
    }

    protected function getType(): string {
        return 'line';
    }

    protected function buildCashFlowSeries(array $filters): array {
        [$from, $to] = $this->getRangeBounds($filters);
        $groupBy = $filters['group'] ?? 'day';

        $revenueData = [];
        $commissionData = [];
        $labels = [];

        $current = $from->copy();
        while ($current->lte($to)) {
            $start = $current->copy();
            $end = $current->copy();

            switch ($groupBy) {
                case 'day':
                    $end->endOfDay();
                    $labels[] = $current->format('d/m');
                    break;
                case 'week':
                    $end->endOfWeek();
                    $labels[] = 'Tuần ' . $current->week;
                    break;
                case 'month':
                    $end->endOfMonth();
                    $labels[] = $current->format('m/Y');
                    break;
            }

            // Doanh thu trong khoảng thời gian
            $revenue = Payment::where('status', 'verified')
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount');

            // Hoa hồng đã chi trong khoảng thời gian
            $commission = CommissionItem::where('status', 'paid')
                ->whereBetween('updated_at', [$start, $end])
                ->sum('amount');

            $revenueData[] = $revenue;
            $commissionData[] = $commission;

            switch ($groupBy) {
                case 'day':
                    $current->addDay();
                    break;
                case 'week':
                    $current->addWeek();
                    break;
                case 'month':
                    $current->addMonth();
                    break;
            }
        }

        return [
            'datasets' => [
                [
                    'label' => 'Doanh thu',
                    'data' => $revenueData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                ],
                [
                    'label' => 'Hoa hồng đã chi',
                    'data' => $commissionData,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                ],
            ],
            'labels' => $labels,
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
