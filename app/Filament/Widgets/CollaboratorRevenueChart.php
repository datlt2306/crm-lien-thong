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
        $tz = DashboardCacheService::getTimezone();

        // Tổng doanh thu hoa hồng theo mọi cấp CTV dựa trên CommissionItem.recipient_collaborator_id
        $itemsQuery = CommissionItem::query()
            ->with(['recipient', 'commission.payment'])
            ->whereHas('commission.payment', function ($q) use ($filters) {
                $this->applyFilters($q, $filters, 'verified_at')
                    ->whereNotNull('verified_at')
                    ->where('status', Payment::STATUS_VERIFIED);
            });

        $items = $itemsQuery->get(['recipient_collaborator_id', 'amount']);

        // Fallback toàn thời gian nếu RANGE trống
        if ($items->isEmpty()) {
            $items = CommissionItem::query()
                ->with(['recipient', 'commission.payment'])
                ->whereHas('commission.payment', function ($q) use ($filters) {
                    $q->whereNotNull('verified_at')
                        ->where('status', Payment::STATUS_VERIFIED);
                    if (!empty($filters['program_type'])) {
                        $q->where('program_type', $filters['program_type']);
                    }
                })
                ->get(['recipient_collaborator_id', 'amount']);
        }

        $sumByCollaborator = [];
        foreach ($items as $it) {
            $name = optional($it->recipient)->full_name ?? 'Khác';
            $sumByCollaborator[$name] = ($sumByCollaborator[$name] ?? 0) + (float) $it->amount;
        }

        // Sắp xếp giảm dần và lấy top 10, gộp phần còn lại thành "Khác" nếu cần
        arsort($sumByCollaborator);
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
