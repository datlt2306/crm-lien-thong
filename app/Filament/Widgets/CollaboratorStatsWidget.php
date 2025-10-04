<?php

namespace App\Filament\Widgets;

use App\Models\Collaborator;
use App\Models\Payment;
use App\Models\Student;
use App\Models\CommissionItem;
use App\Services\DashboardCacheService;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CollaboratorStatsWidget extends BaseWidget {
    use WithDashboardFilters;
    protected ?string $heading = 'Thống kê Cộng tác viên';
    protected ?string $pollingInterval = '120s';

    protected function getCards(): array {
        $filters = $this->filters;

        $stats = DashboardCacheService::remember("admin:collaborator-stats", $filters, DashboardCacheService::DEFAULT_TTL_SECONDS, function () use ($filters) {
            return $this->calculateCollaboratorStats($filters);
        });

        return [
            Stat::make('Tổng số CTV', (string) $stats['total_collaborators'])->description('Tất cả cộng tác viên')->color('info'),
            Stat::make('CTV hoạt động', (string) $stats['active_collaborators'])->description('Có doanh thu trong kỳ')->color('success'),
            Stat::make('CTV mới', (string) $stats['new_collaborators'])->description('Đăng ký trong kỳ')->color('warning'),
            Stat::make('Tổng doanh thu CTV', $stats['total_revenue'])->description('VND')->color('primary'),
        ];
    }

    protected function calculateCollaboratorStats(array $filters): array {
        $range = $this->getRangeBounds($filters);
        $from = $range['from'];
        $to = $range['to'];

        // Tổng số CTV
        $totalCollaborators = Collaborator::count();

        // CTV hoạt động (có doanh thu trong kỳ)
        $activeCollaborators = Collaborator::whereHas('payments', function (Builder $query) use ($from, $to) {
            $query->where('status', 'verified')
                ->whereBetween('created_at', [$from, $to]);
        })->count();

        // CTV mới trong kỳ
        $newCollaborators = Collaborator::whereBetween('created_at', [$from, $to])->count();

        // Tổng doanh thu của tất cả CTV trong kỳ
        $totalRevenue = Payment::where('status', 'verified')
            ->whereNotNull('primary_collaborator_id')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        return [
            'total_collaborators' => $totalCollaborators,
            'active_collaborators' => $activeCollaborators,
            'new_collaborators' => $newCollaborators,
            'total_revenue' => number_format($totalRevenue, 0, ',', '.') . ' VND',
        ];
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
