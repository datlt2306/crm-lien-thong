<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\CommissionItem;
use App\Models\Student;
use App\Models\Wallet;
use App\Services\DashboardCacheService;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class CtvUnifiedStats extends BaseWidget {
    use WithDashboardFilters;
    protected ?string $pollingInterval = '120s';

    protected function getCards(): array {
        $filters = $this->filters;
        $user = Auth::user();
        if (!$user) return [];

        $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
        if (!$collaborator) return [];

        $collabId = $collaborator->id;

        $stats = DashboardCacheService::remember("ctv:unified:{$collabId}", $filters, DashboardCacheService::DEFAULT_TTL_SECONDS, function () use ($filters, $collabId) {
            return $this->calculateStats($filters, $collabId);
        });

        return [
            Stat::make('Số dư ví', $stats['wallet_balance'])
                ->description('Số tiền khả dụng')
                ->descriptionIcon('heroicon-m-wallet')
                ->color('success'),

            Stat::make('Hoa hồng chờ', $stats['pending_commission'])
                ->description('Chờ xác nhận/nhập học')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning'),

            Stat::make('Học viên đã nộp học phí', (string) $stats['paid_students'])
                ->description('HV của bạn đã xác thực')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('info'),

            Stat::make('Tổng học viên', (string) $stats['total_students'])
                ->description('Toàn bộ HV của bạn')
                ->descriptionIcon('heroicon-m-users')
                ->color('gray'),

            Stat::make('Học viên mới', (string) $stats['new_students'])
                ->description('HV của bạn (30 ngày)')
                ->descriptionIcon('heroicon-m-user-plus')
                ->color('primary'),

            Stat::make('Tỷ lệ chuyển đổi', $stats['conversion_rate'])
                ->description('Xác thực / Tổng số HV')
                ->descriptionIcon('heroicon-m-arrow-trending-up')
                ->color('success'),
        ];
    }

    protected function calculateStats(array $filters, int $collabId): array {
        [$from, $to] = $this->getRangeBounds($filters);

        // 1. Wallet Balance
        $wallet = Wallet::where('collaborator_id', $collabId)->first();
        $balance = $wallet ? $wallet->balance : 0;

        // 2. Pending Commission (Pending + Payable + Payment Confirmed)
        $pendingCommission = CommissionItem::where('recipient_collaborator_id', $collabId)
            ->whereIn('status', [
                CommissionItem::STATUS_PENDING,
                CommissionItem::STATUS_PAYABLE,
                CommissionItem::STATUS_PAYMENT_CONFIRMED
            ])
            ->sum('amount');

        // 3. Paid Students (Verified payment)
        $paidStudents = Student::where('collaborator_id', $collabId)
            ->whereHas('payment', function (Builder $query) {
                $query->where('status', Payment::STATUS_VERIFIED);
            })->count();

        // 4. Total Students (Lifetime)
        $totalStudents = Student::where('collaborator_id', $collabId)->count();

        // 5. New Students (Targeted period)
        $newStudents = Student::where('collaborator_id', $collabId)
            ->whereBetween('created_at', [$from, $to])->count();

        // 6. Conversion Rate (Lifetime)
        $conversionRate = $totalStudents > 0 ? round(($paidStudents / $totalStudents) * 100, 1) : 0;

        return [
            'wallet_balance' => number_format($balance) . ' VND',
            'pending_commission' => number_format($pendingCommission) . ' VND',
            'paid_students' => $paidStudents,
            'total_students' => $totalStudents,
            'new_students' => $newStudents,
            'conversion_rate' => $conversionRate . '%',
        ];
    }

    protected function getRangeBounds(array $filters): array {
        $tz = DashboardCacheService::getTimezone();
        $now = CarbonImmutable::now($tz);
        
        // Default to last 30 days if not set
        $range = $filters['range'] ?? 'last_30_days';
        
        switch ($range) {
            case 'today':
                return [$now->startOfDay(), $now->endOfDay()];
            case 'last_7_days':
                return [$now->subDays(6)->startOfDay(), $now->endOfDay()];
            case 'this_month':
                return [$now->startOfMonth(), $now->endOfMonth()];
            case 'custom':
                $from = !empty($filters['from']) ? CarbonImmutable::parse($filters['from'], $tz)->startOfDay() : $now->subDays(29)->startOfDay();
                $to = !empty($filters['to']) ? CarbonImmutable::parse($filters['to'], $tz)->endOfDay() : $now->endOfDay();
                return [$from, $to];
            case 'last_30_days':
            default:
                return [$now->subDays(29)->startOfDay(), $now->endOfDay()];
        }
    }
}
