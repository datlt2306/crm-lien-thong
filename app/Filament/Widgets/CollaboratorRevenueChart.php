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
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
    ];
    protected ?string $maxHeight = '300px';

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

        // Lấy dữ liệu gộp theo cộng tác viên và trạng thái thanh toán
        $payments = Payment::query()
            ->join('collaborators', 'payments.primary_collaborator_id', '=', 'collaborators.id')
            ->whereIn('payments.status', [Payment::STATUS_SUBMITTED, Payment::STATUS_VERIFIED])
            ->whereBetween('payments.created_at', [$from, $to])
            ->when(!empty($filters['program_type']), function ($q) use ($filters) {
                $q->where('payments.program_type', strtolower($filters['program_type']));
            })
            ->selectRaw('
                collaborators.full_name, 
                SUM(CASE WHEN payments.status = \'' . Payment::STATUS_VERIFIED . '\' THEN payments.amount ELSE 0 END) as verified_amount,
                SUM(payments.amount) as total_amount
            ')
            ->groupBy('collaborators.id', 'collaborators.full_name')
            ->orderByDesc('total_amount')
            ->get();

        $verifiedByCollaborator = [];
        $totalByCollaborator = [];
        
        foreach ($payments as $p) {
            $verifiedByCollaborator[$p->full_name] = (float) $p->verified_amount;
            $totalByCollaborator[$p->full_name] = (float) $p->total_amount;
        }

        // Lấy top 10 theo tổng doanh thu
        $topLabels = array_slice(array_keys($totalByCollaborator), 0, 10);
        
        $finalLabels = [];
        $verifiedData = [];
        $totalData = [];

        foreach ($topLabels as $label) {
            $finalLabels[] = $label;
            $verifiedData[] = $verifiedByCollaborator[$label];
            $totalData[] = $totalByCollaborator[$label];
        }

        if (count($totalByCollaborator) > 10) {
            $othersTotal = array_sum(array_slice($totalByCollaborator, 10));
            $othersVerified = array_sum(array_slice($verifiedByCollaborator, 10));
            $finalLabels[] = 'Khác';
            $totalData[] = $othersTotal;
            $verifiedData[] = $othersVerified;
        }

        // Nếu vẫn trống
        if (empty($finalLabels)) {
            $finalLabels = ['—'];
            $verifiedData = [0];
            $totalData = [0];
        }

        return [
            'labels' => $finalLabels,
            'datasets' => [
                [
                    'label' => 'Thực nhận (Đã xác minh)',
                    'data' => $verifiedData,
                    'backgroundColor' => '#10b981', // Green-500
                    'borderColor' => '#059669',
                ],
                [
                    'label' => 'Tổng doanh thu (Dự kiến)',
                    'data' => $totalData,
                    'backgroundColor' => '#6366f1', // Indigo-500
                    'borderColor' => '#4f46e5',
                ],
            ],
        ];
    }
}
