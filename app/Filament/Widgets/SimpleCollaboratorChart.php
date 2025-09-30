<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\CommissionItem;
use App\Services\DashboardCacheService;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;

class SimpleCollaboratorChart extends ChartWidget {
    use WithDashboardFilters;
    protected ?string $heading = 'Doanh thu theo CTV (Simple)';
    protected ?string $pollingInterval = '120s';

    protected function getData(): array {
        $filters = $this->filters;
        
        // Lấy top 5 CTV có doanh thu cao nhất
        $collaborators = Payment::where('status', 'verified')
            ->whereNotNull('collaborator_id')
            ->selectRaw('collaborator_id, SUM(amount) as total_revenue')
            ->groupBy('collaborator_id')
            ->orderBy('total_revenue', 'desc')
            ->limit(5)
            ->get();
        
        $labels = [];
        $dataset = [];
        
        foreach ($collaborators as $collab) {
            $labels[] = 'CTV ' . $collab->collaborator_id;
            $dataset[] = (float) $collab->total_revenue;
        }
        
        // Nếu không có dữ liệu, tạo dữ liệu mẫu
        if (empty($labels)) {
            $labels = ['CTV 1', 'CTV 2', 'CTV 3', 'CTV 4', 'CTV 5'];
            $dataset = [500000, 400000, 300000, 200000, 100000];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Doanh thu (VND)',
                    'data' => $dataset,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59,130,246,0.1)',
                ]
            ],
        ];
    }

    protected function getType(): string {
        return 'bar';
    }
}
