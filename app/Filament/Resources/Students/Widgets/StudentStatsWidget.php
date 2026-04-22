<?php

namespace App\Filament\Resources\Students\Widgets;

use App\Filament\Resources\Students\StudentResource;
use App\Models\Payment;
use App\Models\Student;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StudentStatsWidget extends BaseWidget
{
    public static function canView(): bool
    {
        return \Illuminate\Support\Facades\Auth::user()?->role !== 'accountant';
    }

    protected function getStats(): array
    {
        $query = StudentResource::getEloquentQuery();

        // 1. Sinh viên chưa nộp tiền (Không có payment hoặc payment status là not_paid)
        $unpaidCount = (clone $query)
            ->where(function ($query) {
                $query->whereDoesntHave('payment')
                    ->orWhereHas('payment', fn ($q) => $q->where('status', Payment::STATUS_NOT_PAID));
            })
            ->count();

        // 2. Tổng số tiền (Đã xác nhận)
        $totalAmount = Payment::query()
            ->whereIn('student_id', (clone $query)->select('id'))
            ->where('status', Payment::STATUS_VERIFIED)
            ->sum('amount');

        // 3. Thiếu phiếu thu (Đã nộp tiền nhưng chưa có receipt_path)
        $missingReceiptCount = (clone $query)
            ->whereHas('payment', function ($q) {
                $q->where('status', Payment::STATUS_VERIFIED)
                  ->whereNull('receipt_path');
            })
            ->count();

        return [
            Stat::make('Chưa nộp tiền', $unpaidCount)
                ->description('Sinh viên chưa nộp lệ phí')
                ->descriptionIcon('heroicon-m-clock')
                ->color('warning')
                ->url(StudentResource::getUrl('index', [
                    'tableFilters[payment_status][value]' => Payment::STATUS_NOT_PAID
                ])),

            Stat::make('Thiếu hóa đơn', $missingReceiptCount)
                ->description('Đã xác nhận, chưa có hóa đơn')
                ->descriptionIcon('heroicon-m-document-minus')
                ->color('danger')
                ->url(StudentResource::getUrl('index', [
                    'tableFilters[missing_receipt][value]' => '1'
                ])),

            Stat::make('Tổng tiền đã thu', number_format($totalAmount, 0, ',', '.') . ' đ')
                ->description('Tiền lệ phí đã xác nhận')
                ->descriptionIcon('heroicon-m-banknotes')
                ->color('success'),
        ];
    }
}
