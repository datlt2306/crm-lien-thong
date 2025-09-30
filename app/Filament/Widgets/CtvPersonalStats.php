<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\CommissionItem;
use App\Models\Student;
use App\Services\DashboardCacheService;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CtvPersonalStats extends BaseWidget {
    use WithDashboardFilters;
    protected ?string $pollingInterval = '120s';

    protected function getCards(): array {
        $filters = $this->filters;
        $userId = Auth::id();

        $stats = DashboardCacheService::remember("ctv:personal:{$userId}", $filters, DashboardCacheService::DEFAULT_TTL_SECONDS, function () use ($filters, $userId) {
            return $this->calculateCtvStats($filters, $userId);
        });

        return [
            Stat::make('Tổng học viên', (string) $stats['total_students'])->description('Học viên đã đăng ký')->color('info'),
            Stat::make('Doanh thu cá nhân', $stats['personal_revenue'])->description('VND')->color('success'),
            Stat::make('Hoa hồng đã nhận', $stats['commission_earned'])->description('VND')->color('warning'),
            Stat::make('Hoa hồng chờ', $stats['pending_commission'])->description('VND')->color('gray'),
        ];
    }

    protected function calculateCtvStats(array $filters, int $userId): array {
        [$from, $to] = $this->getRangeBounds($filters);

        // Tổng học viên
        $totalStudents = Student::whereHas('payment', function (Builder $query) use ($userId) {
            $query->where('primary_collaborator_id', $userId);
        })->count();

        // Doanh thu cá nhân trong khoảng thời gian
        $personalRevenue = Payment::where('primary_collaborator_id', $userId)
            ->where('status', 'verified')
            ->whereBetween('created_at', [$from, $to])
            ->sum('amount');

        // Hoa hồng đã nhận
        $commissionEarned = CommissionItem::where('recipient_collaborator_id', $userId)
            ->where('status', 'paid')
            ->sum('amount');

        // Hoa hồng chờ
        $pendingCommission = CommissionItem::where('recipient_collaborator_id', $userId)
            ->where('status', 'pending')
            ->sum('amount');

        return [
            'total_students' => $totalStudents,
            'personal_revenue' => number_format($personalRevenue) . ' VND',
            'commission_earned' => number_format($commissionEarned) . ' VND',
            'pending_commission' => number_format($pendingCommission) . ' VND',
        ];
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
}
