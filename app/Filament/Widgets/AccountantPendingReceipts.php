<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use App\Services\DashboardCacheService;
use App\Filament\Widgets\Concerns\WithDashboardFilters;
use Carbon\CarbonImmutable;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class AccountantPendingReceipts extends BaseWidget {
    use WithDashboardFilters;
    protected ?string $pollingInterval = '30s';

    protected function getCards(): array {
        try {
            $filters = $this->filters;

            $stats = DashboardCacheService::remember('accountant:pending_receipts', $filters, DashboardCacheService::DEFAULT_TTL_SECONDS, function () use ($filters) {
                return $this->calculatePendingReceiptsStats($filters);
            });

            return [
                Stat::make('Phiếu thu chờ xử lý', (string) $stats['pending_count'])->description('Cần upload phiếu thu')->color('warning'),
                Stat::make('Tổng giá trị chờ', $stats['pending_amount'])->description('VND')->color('info'),
                Stat::make('Phiếu thu đã xử lý', (string) $stats['processed_count'])->description('Trong khoảng thời gian')->color('success'),
                Stat::make('Tỷ lệ xử lý', $stats['processing_rate'])->description('Phiếu thu đã xử lý / Tổng phiếu thu')->color('gray'),
            ];
        } catch (\Exception $e) {
            // Fallback khi có lỗi
            return [
                Stat::make('Lỗi tải dữ liệu', 'Không thể tải thống kê')->description('Vui lòng thử lại sau')->color('danger'),
            ];
        }
    }

    protected function calculatePendingReceiptsStats(array $filters): array {
        try {
            [$from, $to] = $this->getRangeBounds($filters);

            // Giới hạn timeout cho query
            DB::statement('SET SESSION wait_timeout = 10');

            // Phiếu thu chờ xử lý (verified nhưng chưa có receipt)
            $pendingCount = Payment::where('status', 'verified')
                ->whereNull('receipt_path')
                ->count();

            $pendingAmount = Payment::where('status', 'verified')
                ->whereNull('receipt_path')
                ->sum('amount');

            // Phiếu thu đã xử lý trong khoảng thời gian
            $processedCount = Payment::where('status', 'verified')
                ->whereNotNull('receipt_path')
                ->whereBetween('updated_at', [$from, $to])
                ->count();

            // Tổng phiếu thu trong khoảng thời gian
            $totalCount = Payment::where('status', 'verified')
                ->whereBetween('updated_at', [$from, $to])
                ->count();

            // Tỷ lệ xử lý
            $processingRate = $totalCount > 0 ? round(($processedCount / $totalCount) * 100, 1) : 0;

            return [
                'pending_count' => $pendingCount,
                'pending_amount' => number_format($pendingAmount) . ' VND',
                'processed_count' => $processedCount,
                'processing_rate' => $processingRate . '%',
            ];
        } catch (\Exception $e) {
            // Fallback khi query timeout
            return [
                'pending_count' => 0,
                'pending_amount' => '0 VND',
                'processed_count' => 0,
                'processing_rate' => '0%',
            ];
        }
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
