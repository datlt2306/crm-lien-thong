<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Services\DashboardCacheService;
use Carbon\CarbonImmutable;
use Filament\Widgets\ChartWidget;
use App\Filament\Widgets\Concerns\WithDashboardFilters;

class RevenueOverTime extends ChartWidget {
    use WithDashboardFilters;
    protected ?string $heading = 'Doanh thu theo ngày';
    protected ?string $pollingInterval = '60s';

    protected function getData(): array {
        $filters = $this->filters;
        $data = DashboardCacheService::remember('admin:chart', $filters, DashboardCacheService::DEFAULT_TTL_SECONDS, function () use ($filters) {
            return $this->buildSeries($filters);
        });
        return $data;
    }

    protected function getType(): string {
        return 'line';
    }

    // filters lấy từ trait

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
        $tz = DashboardCacheService::getTimezone();

        $query = Payment::query()
            ->whereNotNull('verified_at')
            ->where('status', Payment::STATUS_VERIFIED)
            ->whereBetween('verified_at', [$from, $to]);

        if (!empty($filters['program_type'])) {
            $query->where('program_type', $filters['program_type']);
        }
        if (!empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }

        $payments = $query->get(['verified_at', 'amount']);

        $buckets = [];
        foreach ($payments as $p) {
            $dateKey = optional($p->verified_at)->setTimezone($tz)?->toDateString();
            if (!$dateKey) {
                continue;
            }
            $buckets[$dateKey] = ($buckets[$dateKey] ?? 0) + (float) $p->amount;
        }

        $labels = [];
        $dataset = [];
        $cursor = CarbonImmutable::parse($from, $tz)->startOfDay();
        $end = CarbonImmutable::parse($to, $tz)->endOfDay();
        while ($cursor->lessThanOrEqualTo($end)) {
            $key = $cursor->toDateString();
            $labels[] = $key;
            $dataset[] = (float) ($buckets[$key] ?? 0);
            $cursor = $cursor->addDay();
        }

        return [
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Doanh thu (₫)',
                    'data' => $dataset,
                    'borderColor' => '#10b981',
                    'backgroundColor' => 'rgba(16,185,129,0.1)'
                ]
            ],
        ];
    }
}
