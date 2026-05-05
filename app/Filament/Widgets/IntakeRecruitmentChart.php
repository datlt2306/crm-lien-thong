<?php

namespace App\Filament\Widgets;

use App\Models\Intake;
use App\Models\Quota;
use App\Models\Student;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class IntakeRecruitmentChart extends ChartWidget {
    use WithDashboardFilters;

    protected ?string $heading = 'Thống kê tuyển sinh theo đợt & Chỉ tiêu';
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
            'plugins' => [
                'legend' => [
                    'position' => 'bottom',
                ],
            ],
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'title' => [
                        'display' => true,
                        'text' => 'Số lượng (Hồ sơ)',
                    ],
                ],
            ],
        ];
    }

    protected function getData(): array {
        $filters = $this->filters;

        // Get active or recent intakes
        $intakes = Intake::orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $labels = [];
        $targetData = [];
        $actualData = [];

        foreach ($intakes as $intake) {
            // Calculate Target Quota (sum of quotas matching filters)
            $quotaQuery = Quota::where('intake_id', $intake->id)
                ->where('status', Quota::STATUS_ACTIVE);
            
            if (!empty($filters['major'])) {
                $quotaQuery->where('major_name', 'like', '%' . $filters['major'] . '%');
            }
            
            if (!empty($filters['program_type'])) {
                // Program type in Quota might be labeled differently, 
                // but let's try to match if possible.
                // For now, if no direct mapping, we might skip or just use total.
            }
            
            $target = (int) $quotaQuery->sum('target_quota');

            // Calculate Actual Recruitment (Count students matching filters and status)
            $studentQuery = Student::where('intake_id', $intake->id)
                ->whereIn('status', [
                    Student::STATUS_SUBMITTED,
                    Student::STATUS_APPROVED,
                    Student::STATUS_ENROLLED
                ]);
            
            // Apply dashboard filters (Major, Program Type)
            if (!empty($filters['major'])) {
                $studentQuery->where('major', $filters['major']);
            }
            if (!empty($filters['program_type'])) {
                $studentQuery->where('program_type', $filters['program_type']);
            }
            
            $actual = $studentQuery->count();
            
            $percent = $target > 0 ? round(($actual / $target) * 100, 1) : 0;
            
            $labels[] = "{$intake->name} ({$percent}%)";
            $targetData[] = $target;
            $actualData[] = $actual;
        }

        if (empty($labels)) {
            return [
                'labels' => ['Không có dữ liệu'],
                'datasets' => [],
            ];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Chỉ tiêu',
                    'data' => $targetData,
                    'backgroundColor' => '#94a3b8', // slate-400
                    'borderColor' => '#64748b',
                    'borderWidth' => 1,
                ],
                [
                    'label' => 'Thực tế (Đã có hồ sơ)',
                    'data' => $actualData,
                    'backgroundColor' => '#3b82f6', // blue-500
                    'borderColor' => '#2563eb',
                    'borderWidth' => 1,
                ],
            ],
        ];
    }
}
