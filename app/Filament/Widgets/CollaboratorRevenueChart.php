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

    protected function getRangeBounds(array $filters): array {
        $tz = DashboardCacheService::getTimezone();
        $now = CarbonImmutable::now($tz);
        switch ($filters['range'] ?? 'last_30_days') {
            case 'today':
                return [$now->startOfDay(), $now->endOfDay()];
            case 'last_7_days':
                return [$now->subDays(6)->startOfDay(), $now->endOfDay()];
            case 'this_month':
                return [$now->startOfMonth(), $now->endOfMonth()];
            case 'custom':
                $from = $filters['from'] ? CarbonImmutable::parse($filters['from'], $tz)->startOfDay() : $now->subDays(29)->startOfDay();
                $to = $filters['to'] ? CarbonImmutable::parse($filters['to'], $tz)->endOfDay() : $now->endOfDay();
                return [$from, $to];
            case 'last_30_days':
            default:
                return [$now->subDays(29)->startOfDay(), $now->endOfDay()];
        }
    }

    protected function buildSeries(array $filters): array {
        [$from, $to] = $this->getRangeBounds($filters);

        // Tổng doanh thu hoa hồng theo mọi cấp CTV dựa trên CommissionItem.recipient_collaborator_id
        $itemsQuery = CommissionItem::query()
            ->with(['recipient', 'commission.payment'])
            ->whereHas('commission.payment', function ($q) use ($from, $to, $filters) {
                $q->whereNotNull('verified_at')
                    ->where('status', Payment::STATUS_VERIFIED)
                    ->whereBetween('verified_at', [$from, $to]);
                if (!empty($filters['program_type'])) {
                    $q->where('program_type', $filters['program_type']);
                }
                if (!empty($filters['organization_id'])) {
                    $q->where('organization_id', $filters['organization_id']);
                }
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
                    if (!empty($filters['organization_id'])) {
                        $q->where('organization_id', $filters['organization_id']);
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
