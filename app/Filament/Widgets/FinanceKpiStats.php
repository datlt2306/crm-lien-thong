<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Models\Student;
use App\Models\CommissionItem;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Support\Facades\DB;

class FinanceKpiStats extends BaseWidget {
    use WithDashboardFilters;

    protected ?string $pollingInterval = '60s';
    protected int|string|array $columnSpan = 'full';
    protected int|array|null $columns = [
        'default' => 2,
        'sm' => 2,
        'lg' => 4,
    ];

    protected function getCards(): array {
        $filters = $this->filters;

        // 1. Lệ phí cần xác minh
        $pendingPaymentsCount = $this->applyFilters(Student::query(), $filters)
            ->whereHas('payment', fn ($q) => $q->where('status', Payment::STATUS_SUBMITTED))
            ->count();
        
        // 2. Hủy / Hoàn tiền
        $rejectedCount = $this->applyFilters(Student::query(), $filters)
            ->where('status', Student::STATUS_REJECTED)
            ->count();

        // 3. Đã nộp lệ phí
        $paidCount = $this->applyFilters(Student::query(), $filters)
            ->whereHas('payment', fn ($q) => $q->where('status', Payment::STATUS_VERIFIED))
            ->count();

        // 4. Chưa nộp lệ phí
        $unpaidCount = $this->applyFilters(Student::query(), $filters)
            ->where(function ($query) {
                $query->whereDoesntHave('payment')
                    ->orWhereHas('payment', function ($q) {
                        $q->where('status', Payment::STATUS_NOT_PAID);
                    });
            })->count();

        // 5. Hoa hồng đã chi
        $paidCommissions = $this->applyFilters(CommissionItem::query(), $filters)
            ->whereIn('status', [
                CommissionItem::STATUS_PAID,
                CommissionItem::STATUS_PAYMENT_CONFIRMED,
                CommissionItem::STATUS_RECEIVED_CONFIRMED
            ])->sum('amount');

        // 6. Hoa hồng chờ chi
        $pendingCommissions = $this->applyFilters(CommissionItem::query(), $filters)
            ->whereIn('status', [
                CommissionItem::STATUS_PENDING,
                CommissionItem::STATUS_PAYABLE
            ])->sum('amount');

        // 7. Tỉ lệ hồ sơ nộp phí
        $totalStudents = $this->applyFilters(Student::query(), $filters)->count();
        $conversionRate = $totalStudents > 0 ? round(($paidCount / $totalStudents) * 100, 1) : 0;

        // 8. Lệ phí thực thu
        $actualRevenue = $this->applyFilters(Payment::query(), $filters)
            ->where('status', Payment::STATUS_VERIFIED)
            ->sum('amount');

        [$from, $until] = $this->getRangeBounds($filters);
        $urlParams = [
            'filters[created_at][range]' => 'custom',
            'filters[created_at][created_from]' => $from?->toDateString(),
            'filters[created_at][created_until]' => $until?->toDateString(),
        ];

        if (!empty($filters['major'])) {
            $urlParams['filters[major][value]'] = $filters['major'];
        }
        if (!empty($filters['program_type'])) {
            $urlParams['filters[program_type][value]'] = $filters['program_type'];
        }

        return [
            Stat::make('Lệ phí cần xác minh', number_format($pendingPaymentsCount))
                ->description('Chờ kế toán xác nhận đã nhận')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('warning')
                ->url(route('filament.admin.resources.students.index', array_merge($urlParams, ['filters[p_status][s]' => Payment::STATUS_SUBMITTED]))),

            Stat::make('Hủy / Hoàn tiền', number_format($rejectedCount))
                ->description('SV bỏ học hoặc hoàn phí')
                ->descriptionIcon('heroicon-m-x-circle')
                ->color('danger')
                ->url(route('filament.admin.resources.students.index', array_merge($urlParams, ['filters[status][value]' => Student::STATUS_REJECTED]))),

            Stat::make('Đã nộp lệ phí', number_format($paidCount))
                ->description('Thanh toán đã xác nhận')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success')
                ->url(route('filament.admin.resources.students.index', array_merge($urlParams, ['filters[p_status][s]' => Payment::STATUS_VERIFIED]))),

            Stat::make('Chưa nộp lệ phí', number_format($unpaidCount))
                ->description('Chưa có dữ liệu nộp tiền')
                ->descriptionIcon('heroicon-m-credit-card')
                ->color('warning')
                ->url(route('filament.admin.resources.students.index', array_merge($urlParams, ['filters[p_status][s]' => 'not_paid']))),

            Stat::make('Hoa hồng đã chi', number_format($paidCommissions) . ' đ')
                ->description('Đã xác nhận thanh toán')
                ->descriptionIcon('heroicon-m-check-badge')
                ->color('success')
                ->url(route('filament.admin.resources.commissions.index', ['filters[status][value]' => 'payment_confirmed'])),

            Stat::make('Hoa hồng chờ chi', number_format($pendingCommissions) . ' đ')
                ->description('Chờ nhập học/đến hạn')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(route('filament.admin.resources.commissions.index', ['filters[status][value]' => 'pending'])),

            Stat::make('Tỉ lệ Hồ sơ/Lệ phí', $conversionRate . '%')
                ->description('Số SV chưa nộp tiền / Tổng hồ sơ')
                ->descriptionIcon('heroicon-m-presentation-chart-line')
                ->color('info')
                ->url(route('filament.admin.resources.students.index')),

            Stat::make('Lệ phí thực thu', number_format($actualRevenue) . ' đ')
                ->description('Tổng tiền đã xác nhận')
                ->descriptionIcon('heroicon-m-currency-dollar')
                ->color('success')
                ->url(route('filament.admin.resources.payments.index')),
        ];
    }
}
