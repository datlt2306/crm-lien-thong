<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class ProgramTypeDistributionChart extends ChartWidget {
    use WithDashboardFilters;

    protected ?string $heading = 'Thống kê theo Hệ đào tạo';
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 1,
        'xl' => 2,
    ];
    protected ?string $pollingInterval = '120s';

    protected function getType(): string {
        return 'doughnut';
    }

    protected function getOptions(): ?array {
        return [
            'layout' => [
                'padding' => 0,
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                    'labels' => [
                        'padding' => 16,
                        'usePointStyle' => true,
                        'pointStyleWidth' => 10,
                        'boxWidth' => 10,
                        'boxHeight' => 10,
                        'font' => [
                            'size' => 12,
                        ],
                    ],
                ],
            ],
            'maintainAspectRatio' => false,
            'cutout' => '65%',
        ];
    }

    protected function getData(): array {
        $filters = $this->filters;
        [$from, $to] = $this->getRangeBounds($filters);

        $data = $this->applyFilters(Student::query(), $filters)
            ->select('program_type', DB::raw('count(*) as count'))
            ->groupBy('program_type')
            ->get();

        $labels = [];
        $counts = [];

        $programLabels = [
            'regular' => 'Chính quy',
            'REGULAR' => 'Chính quy',
            'part_time' => 'Vừa học vừa làm',
            'PART_TIME' => 'Vừa học vừa làm',
            'distance' => 'Từ xa',
            'DISTANCE' => 'Từ xa',
        ];

        foreach ($data as $item) {
            $labels[] = $programLabels[$item->program_type] ?? ($item->program_type ?: 'Chưa xác định');
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
                        '#3b82f6', // blue
                        '#10b981', // green
                        '#f59e0b', // amber
                        '#ef4444', // red
                    ],
                ]
            ],
        ];
    }
}
