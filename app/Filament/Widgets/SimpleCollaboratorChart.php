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
        $range = $this->getRangeBounds($filters);
        $from = $range['from'];
        $to = $range['to'];

        // Lấy top 10 CTV có doanh thu cao nhất trong kỳ
        $collaborators = Payment::where('status', 'verified')
            ->whereNotNull('primary_collaborator_id')
            ->whereBetween('created_at', [$from, $to])
            ->with('primaryCollaborator')
            ->selectRaw('primary_collaborator_id, SUM(amount) as total_revenue, COUNT(*) as payment_count')
            ->groupBy('primary_collaborator_id')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get();

        $labels = [];
        $revenueData = [];
        $paymentCountData = [];

        foreach ($collaborators as $collab) {
            $collaboratorName = $collab->primaryCollaborator->user->name ?? 'CTV ' . $collab->primary_collaborator_id;
            $labels[] = $collaboratorName;
            $revenueData[] = (float) $collab->total_revenue;
            $paymentCountData[] = (int) $collab->payment_count;
        }

        // Nếu không có dữ liệu, hiển thị thông báo
        if (empty($labels)) {
            $labels = ['Không có dữ liệu'];
            $revenueData = [0];
            $paymentCountData = [0];
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Doanh thu (VND)',
                    'data' => $revenueData,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => 'rgba(59,130,246,0.1)',
                    'yAxisID' => 'y',
                ],
                [
                    'label' => 'Số lượng thanh toán',
                    'data' => $paymentCountData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16,185,129,0.1)',
                    'yAxisID' => 'y1',
                    'type' => 'line',
                ]
            ],
        ];
    }

    protected function getType(): string {
        return 'bar';
    }

    protected function getRangeBounds(array $filters): array {
        $now = CarbonImmutable::now();

        $range = $filters['range'] ?? '30d';

        switch ($range) {
            case '7d':
                $from = $now->subDays(7)->startOfDay();
                $to = $now->endOfDay();
                break;
            case '30d':
                $from = $now->subDays(30)->startOfDay();
                $to = $now->endOfDay();
                break;
            case '90d':
                $from = $now->subDays(90)->startOfDay();
                $to = $now->endOfDay();
                break;
            case '1y':
                $from = $now->subYear()->startOfDay();
                $to = $now->endOfDay();
                break;
            default:
                $from = $now->subDays(30)->startOfDay();
                $to = $now->endOfDay();
        }

        return [
            'from' => $from,
            'to' => $to,
        ];
    }
}
