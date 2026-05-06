<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Models\Payment;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PaymentStatusChart extends ChartWidget {
    use WithDashboardFilters;

    protected ?string $heading = 'Tỉ lệ Lệ phí / Hồ sơ';
    
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'md' => 1,
        'lg' => 1,
    ];
    
    protected ?string $pollingInterval = '120s';
    protected ?string $maxHeight = '280px';

    public function getHeading(): string {
        $filters = $this->filters;
        $total = $this->applyFilters(Student::query(), $filters)->count();
        $paid = $this->applyFilters(Student::query(), $filters)
            ->whereHas('payment', fn($q) => $q->where('status', Payment::STATUS_VERIFIED))
            ->count();
        $rate = $total > 0 ? round(($paid / $total) * 100, 1) : 0;
        
        return "Tỉ lệ Lệ phí / Hồ sơ ({$rate}%)";
    }

    protected function getType(): string {
        return 'doughnut';
    }

    protected function getData(): array {
        $filters = $this->filters;

        $totalStudents = $this->applyFilters(Student::query(), $filters)->count();
        
        if ($totalStudents === 0) {
            return [
                'labels' => ['Không có dữ liệu'],
                'datasets' => [
                    [
                        'data' => [1],
                        'backgroundColor' => ['#f3f4f6'],
                    ],
                ],
            ];
        }

        // Count by payment status
        $paidCount = $this->applyFilters(Student::query(), $filters)
            ->whereHas('payment', fn($q) => $q->where('status', Payment::STATUS_VERIFIED))
            ->count();
            
        $pendingCount = $this->applyFilters(Student::query(), $filters)
            ->whereHas('payment', fn($q) => $q->where('status', Payment::STATUS_SUBMITTED))
            ->count();
            
        $rejectedCount = $this->applyFilters(Student::query(), $filters)
            ->where('status', Student::STATUS_REJECTED)
            ->count();
            
        $unpaidCount = $totalStudents - $paidCount - $pendingCount - $rejectedCount;
        if ($unpaidCount < 0) $unpaidCount = 0;

        return [
            'labels' => [
                "Đã nộp ({$paidCount})",
                "Chờ duyệt ({$pendingCount})",
                "Chưa nộp ({$unpaidCount})",
                "Hủy/Hoàn ({$rejectedCount})",
            ],
            'datasets' => [
                [
                    'label' => 'Số lượng học viên',
                    'data' => [$paidCount, $pendingCount, $unpaidCount, $rejectedCount],
                    'backgroundColor' => [
                        '#10b981', // success (green)
                        '#f59e0b', // warning (amber)
                        '#9ca3af', // gray
                        '#ef4444', // danger (red)
                    ],
                    'hoverOffset' => 4
                ],
            ],
        ];
    }

    protected function getOptions(): ?array {
        return [
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'bottom',
                    'labels' => [
                        'usePointStyle' => true,
                        'padding' => 15,
                    ],
                ],
                'tooltip' => [
                    'enabled' => true,
                ],
            ],
            'cutout' => '65%',
            'maintainAspectRatio' => false,
        ];
    }
}
