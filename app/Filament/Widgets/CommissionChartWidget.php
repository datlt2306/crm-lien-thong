<?php

namespace App\Filament\Widgets;

use App\Models\CommissionItem;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CommissionChartWidget extends ChartWidget {
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

        $query = CommissionItem::query();

        // Filter theo role của user
        if ($user->role === 'super_admin') {
            // Super admin thấy tất cả
        } elseif ($user->role === 'ctv') {
            $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                $query->where('recipient_collaborator_id', $collaborator->id);
            }
        } elseif ($user->role === 'chủ đơn vị') {
            $org = \App\Models\Organization::where('owner_id', $user->id)->first();
            if ($org) {
                $query->whereHas('recipient', function ($q) use ($org) {
                    $q->where('organization_id', $org->id);
                });
            }
        }

        // Group by theo thời gian
        $dateFormat = match ($groupBy) {
            'day' => '%Y-%m-%d',
            'week' => '%Y-%u',
            'month' => '%Y-%m',
            'year' => '%Y',
            default => '%Y-%m-%d',
        };

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
                    DB::raw('SUM(CASE WHEN status = "paid" THEN amount ELSE 0 END) as paid_amount'),
                    DB::raw('SUM(CASE WHEN status = "pending" THEN amount ELSE 0 END) as pending_amount'),
                    DB::raw('SUM(CASE WHEN status = "payable" THEN amount ELSE 0 END) as payable_amount'),
                    DB::raw('COUNT(*) as total_count')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } catch (\Exception $e) {
            // Log lỗi và trả về dữ liệu mặc định
            Log::error('CommissionChartWidget error: ' . $e->getMessage());
            $data = collect();
        }

        $labels = [];
        $paidData = [];
        $pendingData = [];
        $payableData = [];

        foreach ($data as $item) {
            try {
                $dateValue = is_string($item->date) ? $item->date : (string) $item->date;
                $labels[] = $this->formatDateLabel($dateValue, $groupBy);
                $paidData[] = (float) $item->paid_amount;
                $pendingData[] = (float) $item->pending_amount;
                $payableData[] = (float) $item->payable_amount;
            } catch (\Exception $e) {
                Log::error('CommissionChartWidget data processing error: ' . $e->getMessage());
                // Skip this item if there's an error
                continue;
            }
        }

        // Đảm bảo tất cả dữ liệu đều là array và không rỗng
        if (empty($labels)) {
            $labels = ['Không có dữ liệu'];
            $paidData = [0];
            $pendingData = [0];
            $payableData = [0];
        }

        // Debug: Log thông tin về dữ liệu
        Log::info('CommissionChartWidget data:', [
            'labels_count' => count($labels),
            'paid_data_count' => count($paidData),
            'pending_data_count' => count($pendingData),
            'payable_data_count' => count($payableData),
        ]);

        // Đảm bảo tất cả dữ liệu đều là string hoặc number
        $safeLabels = array_map(function ($label) {
            return is_string($label) ? $label : (string) $label;
        }, array_values($labels));

        $safePaidData = array_map(function ($value) {
            return is_numeric($value) ? (float) $value : 0.0;
        }, array_values($paidData));

        $safePendingData = array_map(function ($value) {
            return is_numeric($value) ? (float) $value : 0.0;
        }, array_values($pendingData));

        $safePayableData = array_map(function ($value) {
            return is_numeric($value) ? (float) $value : 0.0;
        }, array_values($payableData));

        $result = [
            'datasets' => [
                [
                    'label' => 'Đã thanh toán',
                    'data' => $safePaidData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => '#10b981',
                    'fill' => false,
                ],
                [
                    'label' => 'Đang chờ',
                    'data' => $safePendingData,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => '#f59e0b',
                    'fill' => false,
                ],
                [
                    'label' => 'Có thể thanh toán',
                    'data' => $safePayableData,
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
            Log::error('CommissionChartWidget formatDateLabel error: ' . $e->getMessage());
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
