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
        [$from, $to] = $this->getRangeBounds($filters);

        // 1. Số sinh viên đã nộp hồ sơ (Submitted status and above)
        $submittedStudents = $this->applyFilters(Student::query(), $filters)
            ->whereIn('status', [Student::STATUS_SUBMITTED, Student::STATUS_APPROVED, Student::STATUS_ENROLLED])
            ->count();

        // 2. Số sinh viên hủy/hoàn tiền (Rejected/Dropped or Reverted Payment)
        $cancelledStudents = $this->applyFilters(Student::query(), $filters)
            ->whereIn('status', [Student::STATUS_REJECTED, Student::STATUS_DROPPED])
            ->count();

        $revertedPayments = $this->applyFilters(Payment::query(), $filters)
            ->where('status', Payment::STATUS_REVERTED)
            ->count();

        // 3. Số sv đã nộp lệ phí (Verified payments)
        $paidFeesCount = $this->applyFilters(Payment::query(), $filters)
            ->where('status', Payment::STATUS_VERIFIED)
            ->count();

        // 4. Số sv chưa nộp lệ phí (Not paid or pending)
        $unpaidFeesCount = $this->applyFilters(Student::query(), $filters)
            ->where(function ($q) {
                $q->whereDoesntHave('payment')
                    ->orWhereHas('payment', fn($p) => $p->where('status', Payment::STATUS_NOT_PAID));
            })
            ->count();

        // 5. Hoa hồng đã chi trả
        $paidCommission = $this->applyFilters(CommissionItem::query(), $filters)
            ->whereIn('status', [
                CommissionItem::STATUS_PAID,
                CommissionItem::STATUS_PAYMENT_CONFIRMED,
                CommissionItem::STATUS_RECEIVED_CONFIRMED
            ])
            ->whereBetween('updated_at', [$from, $to])
            ->sum('amount');

        // 6. Hoa hồng chưa chi trả
        $pendingCommission = $this->applyFilters(CommissionItem::query(), $filters)
            ->whereIn('status', [
                CommissionItem::STATUS_PENDING,
                CommissionItem::STATUS_PAYABLE
            ])
            ->sum('amount');

        // 7. Tỉ lệ Hồ sơ chưa nộp lệ phí
        $totalApplications = $this->applyFilters(Student::query(), $filters)->count();
        $unpaidRate = $totalApplications > 0 ? round(($unpaidFeesCount / $totalApplications) * 100, 1) : 0;

        // 8. Tổng lệ phí thực thu
        $totalRevenue = $this->applyFilters(Payment::query(), $filters)
            ->where('status', Payment::STATUS_VERIFIED)
            ->sum('amount');

        return [
            Stat::make('Hồ sơ đã nộp', (string) $submittedStudents)
                ->description('Trạng thái chờ xác minh trở lên')
                ->icon('heroicon-o-document-check')
                ->color('info'),

            Stat::make('Hủy / Hoàn tiền', (string) ($cancelledStudents + $revertedPayments))
                ->description('SV bỏ học hoặc hoàn phí')
                ->icon('heroicon-o-x-circle')
                ->color('danger'),

            Stat::make('Đã nộp lệ phí', (string) $paidFeesCount)
                ->description('Thanh toán đã xác nhận')
                ->icon('heroicon-o-banknotes')
                ->color('success'),

            Stat::make('Chưa nộp lệ phí', (string) $unpaidFeesCount)
                ->description('Chưa có dữ liệu nộp tiền')
                ->icon('heroicon-o-exclamation-circle')
                ->color('warning'),

            Stat::make('Hoa hồng đã chi', number_format($paidCommission) . ' ₫')
                ->description('Đã xác nhận thanh toán')
                ->icon('heroicon-o-arrow-trending-up')
                ->color('success'),

            Stat::make('Hoa hồng chờ chi', number_format($pendingCommission) . ' ₫')
                ->description('Chờ nhập học/đến hạn')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('Tỉ lệ Hồ sơ/Lệ phí', $unpaidRate . '%')
                ->description('Số SV chưa nộp tiền / Tổng hồ sơ')
                ->icon('heroicon-o-chart-pie')
                ->color('primary'),

            Stat::make('Lệ phí thực thu', number_format($totalRevenue) . ' ₫')
                ->description('Tổng tiền đã xác nhận')
                ->icon('heroicon-o-currency-dollar')
                ->color('success'),
        ];
    }
}
