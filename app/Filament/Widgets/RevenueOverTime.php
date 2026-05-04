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
    protected int|string|array $columnSpan = [
        'default' => 'full',
        'lg' => 2,
    ];

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

    protected function buildSeries(array $filters): array {
        [$from, $to] = $this->getRangeBounds($filters);
        $tz = DashboardCacheService::getTimezone();

        $query = $this->applyFilters(Payment::query(), $filters, 'verified_at')
            ->whereNotNull('verified_at')
            ->where('status', Payment::STATUS_VERIFIED);

        $payments = $query->get(['verified_at', 'amount']);

        // Fallback: nếu khoảng thời gian không có dữ liệu, hiển thị toàn thời gian với cùng filter khác
        if ($payments->isEmpty()) {
            $fallback = Payment::query()
                ->whereNotNull('verified_at')
                ->where('status', Payment::STATUS_VERIFIED);
            if (!empty($filters['program_type'])) {
                $fallback->where('program_type', $filters['program_type']);
            }
            $payments = $fallback->get(['verified_at', 'amount']);
        }

        $buckets = [];
        $group = $filters['group'] ?? 'day'; // day | month | year
        foreach ($payments as $p) {
            $dt = optional($p->verified_at)->setTimezone($tz);
            if (!$dt) {
                continue;
            }
            $dateKey = match ($group) {
                'month' => $dt->format('Y-m'),
                'year' => $dt->format('Y'),
                default => $dt->toDateString(),
            };
            $buckets[$dateKey] = ($buckets[$dateKey] ?? 0) + (float) $p->amount;
        }

        $labels = [];
        $dataset = [];
        if ($group === 'year') {
            $cursor = CarbonImmutable::parse($from, $tz)->startOfYear();
            $end = CarbonImmutable::parse($to, $tz)->endOfYear();
            while ($cursor->lessThanOrEqualTo($end)) {
                $key = $cursor->format('Y');
                $labels[] = $key;
                $dataset[] = (float) ($buckets[$key] ?? 0);
                $cursor = $cursor->addYear();
            }
        } elseif ($group === 'month') {
            $cursor = CarbonImmutable::parse($from, $tz)->startOfMonth();
            $end = CarbonImmutable::parse($to, $tz)->endOfMonth();
            while ($cursor->lessThanOrEqualTo($end)) {
                $key = $cursor->format('Y-m');
                $labels[] = $key;
                $dataset[] = (float) ($buckets[$key] ?? 0);
                $cursor = $cursor->addMonth();
            }
        } else {
            $cursor = CarbonImmutable::parse($from, $tz)->startOfDay();
            $end = CarbonImmutable::parse($to, $tz)->endOfDay();
            while ($cursor->lessThanOrEqualTo($end)) {
                $key = $cursor->toDateString();
                $labels[] = $key;
                $dataset[] = (float) ($buckets[$key] ?? 0);
                $cursor = $cursor->addDay();
            }
        }

        // Nếu vẫn trống, đảm bảo trả về ít nhất một điểm 0
        if (empty($labels)) {
            $labels = [CarbonImmutable::now($tz)->toDateString()];
            $dataset = [0];
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
