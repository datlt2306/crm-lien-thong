<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SimpleAccountantStats extends BaseWidget {
    protected ?string $pollingInterval = null;

    protected function getCards(): array {
        try {
            // Tăng memory limit và execution time
            ini_set('memory_limit', '256M');
            set_time_limit(10);

            // Thống kê chi tiết cho kế toán
            $totalPayments = Payment::count();
            $submittedPayments = Payment::where('status', 'submitted')->count();
            $verifiedPayments = Payment::where('status', 'verified')->count();

            // Thanh toán đã verify nhưng chưa có receipt (cần upload bill)
            $verifiedWithoutReceipt = Payment::where('status', 'verified')
                ->whereNull('receipt_path')
                ->count();

            // Tổng giá trị thanh toán chờ xử lý
            $pendingAmount = Payment::where('status', 'submitted')->sum('amount');

            // Tổng giá trị thanh toán cần upload bill
            $needReceiptAmount = Payment::where('status', 'verified')
                ->whereNull('receipt_path')
                ->sum('amount');

            return [
                Stat::make('Chờ xác nhận', (string) $submittedPayments)
                    ->description('Cần verify thanh toán')
                    ->color('warning'),
                Stat::make('Cần upload bill', (string) $verifiedWithoutReceipt)
                    ->description('Đã verify, chưa có phiếu thu')
                    ->color('danger'),
                Stat::make('Tổng giá trị chờ', number_format($pendingAmount) . ' VND')
                    ->description('Thanh toán chờ verify')
                    ->color('info'),
                Stat::make('Giá trị cần bill', number_format($needReceiptAmount) . ' VND')
                    ->description('Cần upload phiếu thu')
                    ->color('gray'),
            ];
        } catch (\Exception $e) {
            // Fallback khi có lỗi
            return [
                Stat::make('Lỗi tải dữ liệu', 'Không thể tải thống kê')
                    ->description('Vui lòng thử lại sau')
                    ->color('danger'),
            ];
        }
    }
}
