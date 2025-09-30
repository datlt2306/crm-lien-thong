<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Services\DashboardCacheService;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class SimpleRevenueChart extends ChartWidget {
    use WithDashboardFilters;
    protected ?string $heading = 'Doanh thu theo ngày (Simple)';
    protected ?string $pollingInterval = '120s';

    protected function getData(): array {
        $filters = $this->filters;

        // Tạo dữ liệu mẫu nếu không có dữ liệu thật
        $labels = [];
        $dataset = [];

        // Tạo 7 ngày gần đây
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $labels[] = $date->format('d/m');

            // Lấy dữ liệu thật
            $revenue = Payment::where('status', 'verified')
                ->whereDate('verified_at', $date)
                ->sum('amount');
                
            $dataset[] = $revenue ?: 0; // Hiển thị 0 nếu không có dữ liệu
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Doanh thu (VND)',
                    'data' => $dataset,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16,185,129,0.1)',
                    'tension' => 0.4,
                ]
            ],
        ];
    }

    protected function getType(): string {
        return 'line';
    }
}
