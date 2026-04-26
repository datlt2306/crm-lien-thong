<?php

namespace App\Filament\Widgets;

use App\Filament\Widgets\Concerns\WithDashboardFilters;
use App\Models\Payment;
use App\Models\CommissionItem;
use App\Services\DashboardCacheService;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\Auth;

class CollaboratorRevenueChart extends ChartWidget {
    use WithDashboardFilters;

    protected ?string $heading = 'Doanh thu theo CTV';
    protected ?string $pollingInterval = '60s';

    protected function getType(): string {
        return 'bar';
    }

    protected function getData(): array {
        $filters = $this->filters;
        $data = DashboardCacheService::remember('admin:collab_revenue', $filters, DashboardCacheService::DEFAULT_TTL_SECONDS, function () use ($filters) {
            return $this->buildSeries($filters);
        });
        return $data;
    }

    protected function buildSeries(array $filters): array {
        [$from, $to] = $this->getRangeBounds($filters);

        // Sử dụng Query Builder aggregation để tính toán trực tiếp từ DB
        $payments = Payment::query()
            ->join('collaborators', 'payments.primary_collaborator_id', '=', 'collaborators.id')
            ->where('payments.status', Payment::STATUS_VERIFIED)
            ->whereNotNull('payments.verified_at')
            ->whereBetween('payments.verified_at', [$from, $to]) // Sửa lỗi thiếu lọc thời gian
            ->when(!empty($filters['program_type']), function ($q) use ($filters) {
                $q->where('payments.program_type', strtolower($filters['program_type']));
            })
            ->selectRaw('collaborators.full_name, SUM(payments.amount) as total_amount')
            ->groupBy('collaborators.id', 'collaborators.full_name')
            ->orderByDesc('total_amount')
            ->get();

        $sumByCollaborator = [];
        foreach ($payments as $p) {
            $sumByCollaborator[$p->full_name] = (float) $p->total_amount;
        }

        // Lấy top 10 và gộp phần còn lại
        $top = array_slice($sumByCollaborator, 0, 10, true);
        if (count($sumByCollaborator) > 10) {
            $others = array_sum(array_slice($sumByCollaborator, 10, null, true));
            $top['Khác'] = ($top['Khác'] ?? 0) + $others;
        }

        $labels = array_keys($top);
        $data = array_values($top);

        // Nếu vẫn trống, cung cấp dữ liệu 0 để tránh lỗi render
        if (empty($labels)) {
            $labels = ['—'];
            $data = [0];
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Doanh thu (₫) theo CTV',
                'data' => $data,
                'backgroundColor' => '#60a5fa',
                'borderColor' => '#3b82f6',
            ]],
        ];
    }
}
