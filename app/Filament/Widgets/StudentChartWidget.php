<?php

namespace App\Filament\Widgets;

use App\Models\Student;
use Filament\Widgets\ChartWidget;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StudentChartWidget extends ChartWidget {
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

        $query = Student::query();

        // Filter theo role của user
        if ($user->role === 'super_admin') {
            // Super admin thấy tất cả
        } elseif ($user->role === 'ctv') {
            $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                $query->where('referrer_id', $collaborator->id);
            }
        } elseif ($user->role === 'chủ đơn vị') {
            $org = \App\Models\Organization::where('owner_id', $user->id)->first();
            if ($org) {
                $query->whereHas('referrer', function ($q) use ($org) {
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
                    DB::raw('COUNT(*) as total_students'),
                    DB::raw('SUM(CASE WHEN status = "enrolled" THEN 1 ELSE 0 END) as enrolled_count'),
                    DB::raw('SUM(CASE WHEN status = "new" THEN 1 ELSE 0 END) as new_count'),
                    DB::raw('SUM(CASE WHEN status = "contacted" THEN 1 ELSE 0 END) as contacted_count'),
                    DB::raw('SUM(CASE WHEN status = "submitted" THEN 1 ELSE 0 END) as submitted_count'),
                    DB::raw('SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) as approved_count'),
                    DB::raw('SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) as rejected_count')
                )
                ->whereBetween('created_at', [$startDate, $endDate])
                ->groupBy('date')
                ->orderBy('date')
                ->get();
        } catch (\Exception $e) {
            // Log lỗi và trả về dữ liệu mặc định
            Log::error('StudentChartWidget error: ' . $e->getMessage());
            $data = collect();
        }

        $labels = [];
        $totalData = [];
        $enrolledData = [];
        $newData = [];
        $contactedData = [];
        $submittedData = [];
        $approvedData = [];
        $rejectedData = [];

        foreach ($data as $item) {
            try {
                $dateValue = is_string($item->date) ? $item->date : (string) $item->date;
                $labels[] = $this->formatDateLabel($dateValue, $groupBy);
                $totalData[] = (int) $item->total_students;
                $enrolledData[] = (int) $item->enrolled_count;
                $newData[] = (int) $item->new_count;
                $contactedData[] = (int) $item->contacted_count;
                $submittedData[] = (int) $item->submitted_count;
                $approvedData[] = (int) $item->approved_count;
                $rejectedData[] = (int) $item->rejected_count;
            } catch (\Exception $e) {
                Log::error('StudentChartWidget data processing error: ' . $e->getMessage());
                // Skip this item if there's an error
                continue;
            }
        }

        // Đảm bảo tất cả dữ liệu đều là array và không rỗng
        if (empty($labels)) {
            $labels = ['Không có dữ liệu'];
            $totalData = [0];
            $enrolledData = [0];
            $newData = [0];
            $contactedData = [0];
            $submittedData = [0];
            $approvedData = [0];
            $rejectedData = [0];
        }

        // Debug: Log thông tin về dữ liệu
        Log::info('StudentChartWidget data:', [
            'labels_count' => count($labels),
            'total_data_count' => count($totalData),
            'enrolled_data_count' => count($enrolledData),
            'new_data_count' => count($newData),
        ]);

        // Đảm bảo tất cả dữ liệu đều là string hoặc number
        $safeLabels = array_map(function ($label) {
            return is_string($label) ? $label : (string) $label;
        }, array_values($labels));

        $safeTotalData = array_map(function ($value) {
            return is_numeric($value) ? (int) $value : 0;
        }, array_values($totalData));

        $safeEnrolledData = array_map(function ($value) {
            return is_numeric($value) ? (int) $value : 0;
        }, array_values($enrolledData));

        $safeNewData = array_map(function ($value) {
            return is_numeric($value) ? (int) $value : 0;
        }, array_values($newData));

        $safeContactedData = array_map(function ($value) {
            return is_numeric($value) ? (int) $value : 0;
        }, array_values($contactedData));

        $safeSubmittedData = array_map(function ($value) {
            return is_numeric($value) ? (int) $value : 0;
        }, array_values($submittedData));

        $safeApprovedData = array_map(function ($value) {
            return is_numeric($value) ? (int) $value : 0;
        }, array_values($approvedData));

        $safeRejectedData = array_map(function ($value) {
            return is_numeric($value) ? (int) $value : 0;
        }, array_values($rejectedData));

        $result = [
            'datasets' => [
                [
                    'label' => 'Tổng số học viên',
                    'data' => $safeTotalData,
                    'borderColor' => '#3b82f6',
                    'backgroundColor' => '#3b82f6',
                    'fill' => false,
                ],
                [
                    'label' => 'Đã nhập học',
                    'data' => $safeEnrolledData,
                    'borderColor' => '#10b981',
                    'backgroundColor' => '#10b981',
                    'fill' => false,
                ],
                [
                    'label' => 'Mới',
                    'data' => $safeNewData,
                    'borderColor' => '#8b5cf6',
                    'backgroundColor' => '#8b5cf6',
                    'fill' => false,
                ],
                [
                    'label' => 'Đã liên hệ',
                    'data' => $safeContactedData,
                    'borderColor' => '#06b6d4',
                    'backgroundColor' => '#06b6d4',
                    'fill' => false,
                ],
                [
                    'label' => 'Đã nộp hồ sơ',
                    'data' => $safeSubmittedData,
                    'borderColor' => '#f59e0b',
                    'backgroundColor' => '#f59e0b',
                    'fill' => false,
                ],
                [
                    'label' => 'Đã duyệt',
                    'data' => $safeApprovedData,
                    'borderColor' => '#84cc16',
                    'backgroundColor' => '#84cc16',
                    'fill' => false,
                ],
                [
                    'label' => 'Từ chối',
                    'data' => $safeRejectedData,
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
                        'stepSize' => 1,
                        'callback' => 'function(value) { return value + " học viên"; }'
                    ]
                ]
            ],
            'plugins' => [
                'legend' => [
                    'position' => 'top',
                ],
                'tooltip' => [
                    'callbacks' => [
                        'label' => 'function(context) { return context.dataset.label + ": " + context.parsed.y + " học viên"; }'
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
            Log::error('StudentChartWidget formatDateLabel error: ' . $e->getMessage());
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
