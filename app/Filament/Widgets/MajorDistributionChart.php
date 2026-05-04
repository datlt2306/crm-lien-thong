<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MajorDistributionChart extends ChartWidget {
    use WithDashboardFilters;

    protected ?string $heading = 'Thống kê hồ sơ theo Ngành';
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
        'xl' => 2,
    ];
    protected ?string $pollingInterval = '120s';
    protected ?string $maxHeight = '250px';

    protected function getType(): string {
        return 'bar';
    }

    protected function getData(): array {
        $filters = $this->filters;
        [$from, $to] = $this->getRangeBounds($filters);

        $data = $this->applyFilters(Student::query(), $filters)
            ->select('major', DB::raw('count(*) as count'))
            ->groupBy('major')
            ->orderBy('count', 'desc')
            ->limit(10)
            ->get();

        $labels = [];
        $counts = [];

        foreach ($data as $item) {
            $labels[] = $item->major ?: 'Chưa xác định';
            $counts[] = $item->count;
        }

        if (empty($labels)) {
            $labels = ['Không có dữ liệu'];
            $counts = [0];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Số lượng hồ sơ',
                    'data' => $counts,
                    'backgroundColor' => [
                        '#3b82f6',
                        '#10b981',
                        '#f59e0b',
                        '#ef4444',
                        '#8b5cf6',
                        '#ec4899',
                        '#06b6d4',
                        '#f97316',
                        '#14b8a6',
                        '#6366f1'
                    ],
                ]
            ],
        ];
    }
}
