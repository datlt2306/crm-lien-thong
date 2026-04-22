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
            $submittedPayments = Payment::where('status', Payment::STATUS_SUBMITTED)->count();

            // Thanh toán đã verify nhưng chưa có receipt (cần upload bill)
            $verifiedWithoutReceipt = Payment::where('status', Payment::STATUS_VERIFIED)
                ->whereNull('receipt_path')
                ->count();

            // Tổng giá trị thanh toán chờ xử lý
            $pendingAmount = Payment::where('status', Payment::STATUS_SUBMITTED)->sum('amount');

            // Tổng giá trị thanh toán cần upload bill
            $needReceiptAmount = Payment::where('status', Payment::STATUS_VERIFIED)
                ->whereNull('receipt_path')
                ->sum('amount');

            return [
                Stat::make('Chờ xác nhận', (string) $submittedPayments)
                    ->description('Cần verify thanh toán')
                    ->color('warning')
                    ->url(\App\Filament\Resources\Students\StudentResource::getUrl('index', [
                        'tableFilters[payment_status][value]' => Payment::STATUS_SUBMITTED
                    ])),
                Stat::make('Cần upload bill', (string) $verifiedWithoutReceipt)
                    ->description('Đã verify, chưa có phiếu thu')
                    ->color('danger')
                    ->url(\App\Filament\Resources\Students\StudentResource::getUrl('index', [
                        'tableFilters[missing_receipt][value]' => '1'
                    ])),
                Stat::make('Tổng giá trị chờ', number_format($pendingAmount) . ' VND')
                    ->description('Thanh toán chờ verify')
                    ->color('info')
                    ->url(\App\Filament\Resources\Students\StudentResource::getUrl('index', [
                        'tableFilters[payment_status][value]' => Payment::STATUS_SUBMITTED
                    ])),
                Stat::make('Giá trị cần bill', number_format($needReceiptAmount) . ' VND')
                    ->description('Cần upload phiếu thu')
                    ->color('gray')
                    ->url(\App\Filament\Resources\Students\StudentResource::getUrl('index', [
                        'tableFilters[missing_receipt][value]' => '1'
                    ])),
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
