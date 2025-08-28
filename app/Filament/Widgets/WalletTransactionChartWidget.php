<?php

namespace App\Filament\Widgets;

use App\Models\WalletTransaction;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class WalletTransactionChartWidget extends ChartWidget {
    use InteractsWithPageFilters;




    protected function getData(): array {
        $user = \Illuminate\Support\Facades\Auth::user();

        // Kiểm tra user có tồn tại không
        if (!$user) {
            return [
                'datasets' => [
                    [
                        'label' => 'Không có dữ liệu',
                        'data' => [0],
                        'borderColor' => '#6b7280',
                        'backgroundColor' => '#6b7280',
                        'fill' => false,
                    ],
                ],
                'labels' => ['Không có quyền truy cập'],
            ];
        }

        $startDate = $this->filters['startDate'] ?? now()->startOfMonth();
        $endDate = $this->filters['endDate'] ?? now()->endOfMonth();
        $groupBy = $this->filters['groupBy'] ?? 'day';

        // Chuyển đổi string thành Carbon object nếu cần
        if (is_string($startDate)) {
            $startDate = Carbon::parse($startDate);
        }
        if (is_string($endDate)) {
            $endDate = Carbon::parse($endDate);
        }

        $query = WalletTransaction::query();

        // Filter theo role của user
        if ($user->role === 'super_admin') {
            // Super admin thấy tất cả
        } elseif ($user->role === 'ctv') {
            $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                $wallet = \App\Models\Wallet::where('collaborator_id', $collaborator->id)->first();
                if ($wallet) {
                    $query->where('wallet_id', $wallet->id);
                }
            }
        } elseif ($user->role === 'chủ đơn vị') {
            $org = \App\Models\Organization::where('owner_id', $user->id)->first();
            if ($org) {
                $walletIds = \App\Models\Wallet::whereHas('collaborator', function ($q) use ($org) {
                    $q->where('organization_id', $org->id);
                })->pluck('id');
                $query->whereIn('wallet_id', $walletIds);
            }
        }

        // Group by theo thời gian
        $dateColumn = match ($groupBy) {
            'day' => 'DATE(created_at)',
            'week' => 'YEARWEEK(created_at)',
            'month' => 'DATE_FORMAT(created_at, "%Y-%m")',
            'year' => 'YEAR(created_at)',
            default => 'DATE(created_at)',
        };

        try {
            $data = $query
                ->select(
                    DB::raw("$dateColumn as date"),
                    DB::raw('SUM(CASE WHEN type = "deposit" THEN amount ELSE 0 END) as credit_amount'),
                    DB::raw('SUM(CASE WHEN type = "withdrawal" THEN amount ELSE 0 END) as debit_amount'),
                    DB::raw('COUNT(*) as total_transactions'),
                    DB::raw('COUNT(CASE WHEN type = "deposit" THEN 1 END) as credit_count'),
                    DB::raw('COUNT(CASE WHEN type = "withdrawal" THEN 1 END) as debit_count')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } catch (\Exception $e) {
            // Log lỗi và trả về dữ liệu mặc định
            Log::error('WalletTransactionChartWidget error: ' . $e->getMessage());
            $data = collect();
        }

        $labels = [];
        $creditData = [];
        $debitData = [];
        $netData = [];

        foreach ($data as $item) {
            try {
                $dateValue = is_string($item->date) ? $item->date : (string) $item->date;
                $labels[] = $this->formatDateLabel($dateValue, $groupBy);
                $creditAmount = (float) $item->credit_amount;
                $debitAmount = (float) $item->debit_amount;

                $creditData[] = $creditAmount;
                $debitData[] = $debitAmount;
                $netData[] = $creditAmount - $debitAmount;
            } catch (\Exception $e) {
                Log::error('WalletTransactionChartWidget data processing error: ' . $e->getMessage());
                // Skip this item if there's an error
                continue;
            }
        }

        // Đảm bảo tất cả dữ liệu đều là array và không rỗng
        if (empty($labels)) {
            $labels = ['Không có dữ liệu'];
            $creditData = [0];
            $debitData = [0];
            $netData = [0];
        }

        // Debug: Log thông tin về dữ liệu
        Log::info('WalletTransactionChartWidget data:', [
            'labels_count' => count($labels),
            'credit_data_count' => count($creditData),
            'debit_data_count' => count($debitData),
            'net_data_count' => count($netData),
        ]);

        // Đảm bảo tất cả dữ liệu đều là string hoặc number
        $safeLabels = array_map(function ($label) {
            return is_string($label) ? $label : (string) $label;
        }, array_values($labels));

        $safeCreditData = array_map(function ($value) {
            return is_numeric($value) ? (float) $value : 0.0;
        }, array_values($creditData));

        $safeDebitData = array_map(function ($value) {
            return is_numeric($value) ? (float) $value : 0.0;
        }, array_values($debitData));

        $safeNetData = array_map(function ($value) {
            return is_numeric($value) ? (float) $value : 0.0;
        }, array_values($netData));

        $result = [
            'datasets' => [
                [
                    'label' => 'Thu vào',
                    'data' => $safeCreditData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => '#10b981',
                    'fill' => false,
                ],
                [
                    'label' => 'Chi ra',
                    'data' => $safeDebitData,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => '#ef4444',
                    'fill' => false,
                ],
                [
                    'label' => 'Thuần',
                    'data' => $safeNetData,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => '#3b82f6',
                    'fill' => false,
                ],
            ],
            'labels' => $safeLabels,
        ];

        return $result;
    }

    protected function getType(): string {
        return 'line';
    }

    protected function getOptions(): array {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'scales' => [
                'y' => [
                    'beginAtZero' => true,
                    'ticks' => [
                        'callback' => 'function(value) { return new Intl.NumberFormat("vi-VN", {style: "currency", currency: "VND"}).format(value); }'
                    ]
                ]
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": " + new Intl.NumberFormat("vi-VN", {style: "currency", currency: "VND"}).format(context.parsed.y); }'
                    ]
                ]
            ]
        ];
    }

    private function formatDateLabel(string $date, string $groupBy): string {
        try {
            $parsedDate = Carbon::parse($date);
            return match ($groupBy) {
                'day' => $parsedDate->format('d/m/Y'),
                'week' => 'Tuần ' . $parsedDate->format('W/Y'),
                'month' => $parsedDate->format('m/Y'),
                'year' => $parsedDate->format('Y'),
                default => $parsedDate->format('d/m/Y'),
            };
        } catch (\Exception $e) {
            Log::error('WalletTransactionChartWidget formatDateLabel error: ' . $e->getMessage());
            return 'Invalid Date';
        }
    }

    protected function getFilters(): ?array {
        return [
            'startDate' => [
                'label' => 'Từ ngày',
                'type' => 'date',
                'default' => now()->startOfMonth()->format('Y-m-d'),
            ],
            'endDate' => [
                'label' => 'Đến ngày',
                'type' => 'date',
                'default' => now()->endOfMonth()->format('Y-m-d'),
            ],
            'groupBy' => [
                'label' => 'Nhóm theo',
                'type' => 'select',
                'options' => [
                    'day' => 'Ngày',
                    'week' => 'Tuần',
                    'month' => 'Tháng',
                    'year' => 'Năm',
                ],
                'default' => 'day',
            ],
        ];
    }
}
