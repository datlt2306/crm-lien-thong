<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use App\Models\Payment;
use App\Services\DashboardCacheService;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CtvStudentsWidget extends BaseWidget {
    use WithDashboardFilters;
    protected ?string $pollingInterval = '60s';

    protected function getCards(): array {
        $filters = $this->filters;
        $userId = Auth::id();
        
        $stats = DashboardCacheService::remember("ctv:students:{$userId}", $filters, DashboardCacheService::DEFAULT_TTL_SECONDS, function () use ($filters, $userId) {
            return $this->calculateStudentStats($filters, $userId);
        });

        return [
            Stat::make('Học viên mới', (string) $stats['new_students'])->description('Trong khoảng thời gian')->color('info'),
            Stat::make('Học viên đã thanh toán', (string) $stats['paid_students'])->description('Trong khoảng thời gian')->color('success'),
            Stat::make('Tỷ lệ chuyển đổi', $stats['conversion_rate'])->description('Học viên đã thanh toán / Tổng học viên')->color('warning'),
            Stat::make('Học viên chưa thanh toán', (string) $stats['unpaid_students'])->description('Trong khoảng thời gian')->color('gray'),
        ];
    }

    protected function calculateStudentStats(array $filters, int $userId): array {
        [$from, $to] = $this->getRangeBounds($filters);
        
        // Học viên mới trong khoảng thời gian
        $newStudents = Student::whereHas('payments', function (Builder $query) use ($userId) {
            $query->where('collaborator_id', $userId);
        })->whereBetween('created_at', [$from, $to])->count();

        // Học viên đã thanh toán trong khoảng thời gian
        $paidStudents = Student::whereHas('payments', function (Builder $query) use ($userId) {
            $query->where('collaborator_id', $userId)
                  ->where('status', 'verified');
        })->whereBetween('created_at', [$from, $to])->count();

        // Tổng học viên trong khoảng thời gian
        $totalStudents = Student::whereHas('payments', function (Builder $query) use ($userId) {
            $query->where('collaborator_id', $userId);
        })->whereBetween('created_at', [$from, $to])->count();

        // Học viên chưa thanh toán
        $unpaidStudents = $totalStudents - $paidStudents;

        // Tỷ lệ chuyển đổi
        $conversionRate = $totalStudents > 0 ? round(($paidStudents / $totalStudents) * 100, 1) : 0;

        return [
            'new_students' => $newStudents,
            'paid_students' => $paidStudents,
            'unpaid_students' => $unpaidStudents,
            'conversion_rate' => $conversionRate . '%',
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
