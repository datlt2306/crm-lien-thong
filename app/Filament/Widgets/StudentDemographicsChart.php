<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class StudentDemographicsChart extends ChartWidget {
    use WithDashboardFilters;

    protected ?string $heading = 'Phân tích hồ sơ theo Ngành & Hệ đào tạo';
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 1,
        'lg' => 2,
    ];
    protected ?string $pollingInterval = '120s';
    protected ?string $maxHeight = '300px';

    protected function getType(): string {
        return 'bar';
    }

    protected function getOptions(): ?array {
        return [
            'scales' => [
                'x' => [
                    'stacked' => true,
                ],
                'y' => [
                    'stacked' => true,
                ],
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
        ];
    }

    protected function getData(): array {
        $filters = $this->filters;

        // Get all majors to use as labels
        $majors = $this->applyFilters(Student::query(), $filters)
            ->select('major')
            ->whereNotNull('major')
            ->groupBy('major')
            ->pluck('major')
            ->toArray();

        if (empty($majors)) {
            return [
                'labels' => ['Không có dữ liệu'],
                'datasets' => [],
            ];
        }

        $rawCounts = $this->applyFilters(Student::query(), $filters)
            ->select('major', 'program_type', DB::raw('count(*) as count'))
            ->groupBy('major', 'program_type')
            ->get();

        $programTypes = [
            'regular' => 'Chính quy',
            'part_time' => 'Vừa học vừa làm',
            'distance' => 'Từ xa',
        ];

        $colors = [
            'regular' => '#3b82f6', // blue
            'part_time' => '#10b981', // green
            'distance' => '#f59e0b', // amber
        ];

        $datasets = [];

        foreach ($programTypes as $type => $label) {
            $data = [];
            foreach ($majors as $major) {
                $item = $rawCounts->first(fn($i) => 
                    (strtolower($i->major) === strtolower($major)) && 
                    (strtolower($i->program_type) === strtolower($type))
                );
                $data[] = $item ? $item->count : 0;
            }

            $datasets[] = [
                'label' => $label,
                'data' => $data,
                'backgroundColor' => $colors[$type],
            ];
        }

        return [
            'labels' => $majors,
            'datasets' => $datasets,
        ];
    }
}
