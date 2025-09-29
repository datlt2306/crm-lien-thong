<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Services\DashboardCacheService;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class AdminKpiStats extends BaseWidget {
    use WithDashboardFilters;
    protected ?string $pollingInterval = '60s';

    protected function getCards(): array {
        $filters = $this->filters;
        $stats = DashboardCacheService::remember('admin:kpi', $filters, DashboardCacheService::DEFAULT_TTL_SECONDS, function () use ($filters) {
            return $this->calculateAdminStats($filters);
        });

        return [
            Stat::make('Tổng doanh thu', $stats['total_revenue'])->description('VND')->color('success'),
            Stat::make('Doanh thu trong khoảng', $stats['range_revenue'])->description($stats['range_label'])->color('success'),
            Stat::make('Payments đã xác minh', (string) $stats['verified_count'])->description($stats['range_label'])->color('info'),
            Stat::make('Tỉ lệ xác minh', $stats['verified_rate'])->description($stats['range_label'])->color('warning'),
        ];
    }

    // filters lấy từ trait

    protected function applyCommonScopes(Builder $query, array $filters): Builder {
        if (!empty($filters['program_type'])) {
            $query->where('program_type', $filters['program_type']);
        }
        if (!empty($filters['organization_id'])) {
            $query->where('organization_id', $filters['organization_id']);
        }
        return $query;
    }

    protected function getRangeBounds(array $filters): array {
        $tz = DashboardCacheService::getTimezone();
        $now = CarbonImmutable::now($tz);
        $label = '';
        switch ($filters['range'] ?? 'last_30_days') {
            case 'today':
                $from = $now->startOfDay();
                $to = $now->endOfDay();
                $label = 'Hôm nay';
                break;
            case 'last_7_days':
                $from = $now->subDays(6)->startOfDay();
                $to = $now->endOfDay();
                $label = '7 ngày gần đây';
                break;
            case 'this_month':
                $from = $now->startOfMonth();
                $to = $now->endOfMonth();
                $label = 'Tháng này';
                break;
            case 'custom':
                $from = $filters['from'] ? CarbonImmutable::parse($filters['from'], $tz)->startOfDay() : $now->subDays(29)->startOfDay();
                $to = $filters['to'] ? CarbonImmutable::parse($filters['to'], $tz)->endOfDay() : $now->endOfDay();
                $label = sprintf('%s - %s', $from->toDateString(), $to->toDateString());
                break;
            case 'last_30_days':
            default:
                $from = $now->subDays(29)->startOfDay();
                $to = $now->endOfDay();
                $label = '30 ngày gần đây';
                break;
        }
        return [$from, $to, $label];
    }

    protected function calculateAdminStats(array $filters): array {
        [$from, $to, $label] = $this->getRangeBounds($filters);

        $base = Payment::query();
        $this->applyCommonScopes($base, $filters);

        $totalRevenue = (clone $base)
            ->where('status', Payment::STATUS_VERIFIED)
            ->sum('amount');

        $rangeQuery = (clone $base)
            ->whereBetween('created_at', [$from, $to])
            ->where('status', Payment::STATUS_VERIFIED);

        $rangeRevenue = (clone $rangeQuery)->sum('amount');
        $verifiedCount = (clone $rangeQuery)->count();

        $submittedCount = (clone $base)
            ->whereBetween('created_at', [$from, $to])
            ->whereIn('status', [Payment::STATUS_SUBMITTED, Payment::STATUS_VERIFIED])
            ->count();

        $verifiedRate = $submittedCount > 0 ? round($verifiedCount * 100 / $submittedCount, 2) . '%' : '0%';

        return [
            'total_revenue' => number_format((float) $totalRevenue, 0, '.', ',') . ' ₫',
            'range_revenue' => number_format((float) $rangeRevenue, 0, '.', ',') . ' ₫',
            'verified_count' => $verifiedCount,
            'verified_rate' => $verifiedRate,
            'range_label' => $label,
        ];
    }
}
