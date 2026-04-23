<?php

namespace App\Filament\Widgets\Concerns;

use Livewire\Attributes\On;

trait WithDashboardFilters {
    public array $filters = [
        'range' => 'last_30_days',
        'from' => null,
        'to' => null,
        'program_type' => null,
        'major' => null,
        'group' => 'day', // day | month | year
    ];

    public function mount(): void {
        // Đảm bảo filters được khởi tạo đúng
        if (empty($this->filters)) {
            $this->filters = [
                'range' => 'last_30_days',
                'from' => null,
                'to' => null,
                'program_type' => null,
                'major' => null,
                'group' => 'day',
            ];
        }
    }

    #[On('dashboardFiltersChanged')]
    public function setFilters(array $filters): void {
        // Livewire 3 sends named parameters in an array
        $actualFilters = $filters['filters'] ?? $filters;
        $this->filters = array_merge($this->filters, $actualFilters);
        $this->dispatch('$refresh');
    }

    protected function applyFilters($query, array $filters, string $dateColumn = 'created_at'): \Illuminate\Database\Eloquent\Builder {
        [$from, $to] = $this->getRangeBounds($filters);
        $model = $query->getModel();
        $table = $model->getTable();

        // Apply date range
        $query->whereBetween($table . '.' . $dateColumn, [$from, $to]);

        // Apply program type
        if (!empty($filters['program_type'])) {
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'program_type')) {
                $query->where($table . '.program_type', $filters['program_type']);
            } elseif (method_exists($model, 'student')) {
                $query->whereHas('student', fn($q) => $q->where('program_type', $filters['program_type']));
            } elseif (method_exists($model, 'commission')) {
                $query->whereHas('commission.student', fn($q) => $q->where('program_type', $filters['program_type']));
            }
        }

        // Apply major
        if (!empty($filters['major'])) {
            if (\Illuminate\Support\Facades\Schema::hasColumn($table, 'major')) {
                $query->where($table . '.major', $filters['major']);
            } elseif (method_exists($model, 'student')) {
                $query->whereHas('student', fn($q) => $q->where('major', $filters['major']));
            } elseif (method_exists($model, 'commission')) {
                $query->whereHas('commission.student', fn($q) => $q->where('major', $filters['major']));
            }
        }

        return $query;
    }

    protected function getRangeBounds(array $filters): array {
        $tz = 'Asia/Ho_Chi_Minh'; // Default or from config
        if (class_exists(\App\Services\DashboardCacheService::class)) {
            $tz = \App\Services\DashboardCacheService::getTimezone();
        }
        
        $now = \Carbon\CarbonImmutable::now($tz);
        $label = '';
        
        $range = $filters['range'] ?? 'last_30_days';
        
        switch ($range) {
            case 'today':
                $from = $now->startOfDay();
                $to = $now->endOfDay();
                $label = 'Hôm nay';
                break;
            case 'last_7_days':
            case '7d':
                $from = $now->subDays(6)->startOfDay();
                $to = $now->endOfDay();
                $label = '7 ngày gần đây';
                break;
            case 'this_month':
                $from = $now->startOfMonth();
                $to = $now->endOfMonth();
                $label = 'Tháng này';
                break;
            case 'last_30_days':
            case '30d':
                $from = $now->subDays(29)->startOfDay();
                $to = $now->endOfDay();
                $label = '30 ngày gần đây';
                break;
            case 'custom':
                $from = !empty($filters['from']) ? \Carbon\CarbonImmutable::parse($filters['from'], $tz)->startOfDay() : $now->subDays(29)->startOfDay();
                $to = !empty($filters['to']) ? \Carbon\CarbonImmutable::parse($filters['to'], $tz)->endOfDay() : $now->endOfDay();
                $label = sprintf('%s - %s', $from->toDateString(), $to->toDateString());
                break;
            case '90d':
                $from = $now->subDays(89)->startOfDay();
                $to = $now->endOfDay();
                $label = '90 ngày gần đây';
                break;
            case '1y':
                $from = $now->subYear()->startOfDay();
                $to = $now->endOfDay();
                $label = '1 năm gần đây';
                break;
            default:
                $from = $now->subDays(29)->startOfDay();
                $to = $now->endOfDay();
                $label = '30 ngày gần đây';
                break;
        }
        
        return [$from, $to, $label];
    }
}
