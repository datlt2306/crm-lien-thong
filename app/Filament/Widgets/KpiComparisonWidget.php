<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\CommissionItem;
use App\Models\Student;
use App\Services\DashboardCacheService;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class KpiComparisonWidget extends ChartWidget {
    use WithDashboardFilters;
    protected ?string $heading = 'So sánh KPI theo thời gian';
    protected ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan = 'full';

    protected function getData(): array {
        $filters = $this->filters;
        $data = DashboardCacheService::remember('kpi:comparison', $filters, DashboardCacheService::DEFAULT_TTL_SECONDS, function () use ($filters) {
            return $this->buildComparisonSeries($filters);
        });
        return $data;
    }

    protected function getType(): string {
        return 'line';
    }

    protected function buildComparisonSeries(array $filters): array {
        [$from, $to] = $this->getRangeBounds($filters);
        $groupBy = $filters['group'] ?? 'day';
        
        $revenueData = [];
        $commissionData = [];
        $studentData = [];
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

            // Doanh thu
            $revenue = Payment::where('status', 'verified')
                ->whereBetween('created_at', [$start, $end])
                ->sum('amount');

            // Hoa hồng
            $commission = CommissionItem::where('status', 'paid')
                ->whereBetween('updated_at', [$start, $end])
                ->sum('amount');

            // Học viên mới
            $students = Student::whereBetween('created_at', [$start, $end])->count();

            $revenueData[] = $revenue;
            $commissionData[] = $commission;
            $studentData[] = $students;

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
                    'label' => 'Doanh thu (VND)',
                    'data' => $revenueData,
                    'borderColor' => 'rgb(34, 197, 94)',
                    'backgroundColor' => 'rgba(34, 197, 94, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Hoa hồng (VND)',
                    'data' => $commissionData,
                    'borderColor' => 'rgb(239, 68, 68)',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Học viên mới',
                    'data' => $studentData,
                    'borderColor' => 'rgb(59, 130, 246)',
                    'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
                    'yAxisID' => 'y1',
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

    protected function getOptions(): array {
        return [
            'scales' => [
                'y' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'left',
                    'title' => [
                        'display' => true,
                        'text' => 'Doanh thu & Hoa hồng (VND)',
                    ],
                ],
                'y1' => [
                    'type' => 'linear',
                    'display' => true,
                    'position' => 'right',
                    'title' => [
                        'display' => true,
                        'text' => 'Số học viên',
                    ],
                    'grid' => [
                        'drawOnChartArea' => false,
                    ],
                ],
            ],
        ];
    }
}
