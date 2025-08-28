<?php

namespace App\Filament\Widgets;

use App\Models\Payment;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentChartWidget extends ChartWidget {
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

        $query = Payment::query();

        // Filter theo role của user
        if ($user->role === 'super_admin') {
            // Super admin thấy tất cả
        } elseif ($user->role === 'ctv') {
            $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                $query->where('collaborator_id', $collaborator->id);
            }
        } elseif ($user->role === 'chủ đơn vị') {
            $org = \App\Models\Organization::where('owner_id', $user->id)->first();
            if ($org) {
                $query->whereHas('collaborator', function ($q) use ($org) {
                    $q->where('organization_id', $org->id);
                });
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
                    DB::raw('SUM(CASE WHEN status = "VERIFIED" THEN amount ELSE 0 END) as verified_amount'),
                    DB::raw('SUM(CASE WHEN status = "SUBMITTED" THEN amount ELSE 0 END) as submitted_amount'),
                    DB::raw('SUM(CASE WHEN status = "NOT_PAID" THEN amount ELSE 0 END) as not_paid_amount'),
                    DB::raw('COUNT(*) as total_count')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } catch (\Exception $e) {
            // Log lỗi và trả về dữ liệu mặc định
            Log::error('PaymentChartWidget error: ' . $e->getMessage());
            $data = collect();
        }

        $labels = [];
        $verifiedData = [];
        $submittedData = [];
        $notPaidData = [];

        foreach ($data as $item) {
            try {
                $dateValue = is_string($item->date) ? $item->date : (string) $item->date;
                $labels[] = $this->formatDateLabel($dateValue, $groupBy);
                $verifiedData[] = (float) $item->verified_amount;
                $submittedData[] = (float) $item->submitted_amount;
                $notPaidData[] = (float) $item->not_paid_amount;
            } catch (\Exception $e) {
                Log::error('PaymentChartWidget data processing error: ' . $e->getMessage());
                // Skip this item if there's an error
                continue;
            }
        }

        // Đảm bảo tất cả dữ liệu đều là array và không rỗng
        if (empty($labels)) {
            $labels = ['Không có dữ liệu'];
            $verifiedData = [0];
            $submittedData = [0];
            $notPaidData = [0];
        }

        // Debug: Log thông tin về dữ liệu
        Log::info('PaymentChartWidget data:', [
            'labels_count' => count($labels),
            'verified_data_count' => count($verifiedData),
            'submitted_data_count' => count($submittedData),
            'not_paid_data_count' => count($notPaidData),
        ]);

        // Đảm bảo tất cả dữ liệu đều là string hoặc number
        $safeLabels = array_map(function ($label) {
            return is_string($label) ? $label : (string) $label;
        }, array_values($labels));

        $safeVerifiedData = array_map(function ($value) {
            return is_numeric($value) ? (float) $value : 0.0;
        }, array_values($verifiedData));

        $safeSubmittedData = array_map(function ($value) {
            return is_numeric($value) ? (float) $value : 0.0;
        }, array_values($submittedData));

        $safeNotPaidData = array_map(function ($value) {
            return is_numeric($value) ? (float) $value : 0.0;
        }, array_values($notPaidData));

        $result = [
            'datasets' => [
                [
                    'label' => 'Đã xác nhận',
                    'data' => $safeVerifiedData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => '#10b981',
                    'fill' => false,
                ],
                [
                    'label' => 'Đã nộp (chờ xác minh)',
                    'data' => $safeSubmittedData,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => '#f59e0b',
                    'fill' => false,
                ],
                [
                    'label' => 'Chưa nộp tiền',
                    'data' => $safeNotPaidData,
                    'borderColor' => '#ef4444',
                    'backgroundColor' => '#ef4444',
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
            Log::error('PaymentChartWidget formatDateLabel error: ' . $e->getMessage());
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
