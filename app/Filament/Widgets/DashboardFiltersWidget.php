<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;


class DashboardFiltersWidget extends Widget {
    protected string $view = 'livewire.dashboard-filters';
    protected ?string $heading = 'Bộ lọc';
    protected int|string|array $columnSpan = 'full';

    public array $filters = [
        'range' => 'last_30_days',
        'from' => null,
        'to' => null,
        'program_type' => null,
        'major' => null,
        'group' => 'day',
    ];

    protected function getViewData(): array {
        $majors = \Illuminate\Support\Facades\DB::table('quotas')
            ->select('major_name as id', 'major_name as name')
            ->whereNotNull('major_name')
            ->distinct()
            ->orderBy('major_name')
            ->get();

        return compact('majors');
    }

    public function updated($name, $value): void {
        $this->dispatch('dashboardFiltersChanged', filters: $this->filters);
    }
}
