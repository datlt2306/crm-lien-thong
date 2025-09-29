<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use App\Models\Organization;
use App\Models\Major;

class DashboardFiltersWidget extends Widget {
    protected string $view = 'livewire.dashboard-filters';
    protected ?string $heading = 'Bộ lọc';
    protected int|string|array $columnSpan = 'full';

    public array $filters = [
        'range' => 'last_30_days',
        'from' => null,
        'to' => null,
        'program_type' => null,
        'organization_id' => null,
        'major' => null,
        'group' => 'day',
    ];

    protected function getViewData(): array {
        $organizations = Organization::query()->orderBy('name')->get(['id', 'name']);
        $majors = Major::query()->orderBy('name')->get(['id', 'name']);
        return compact('organizations', 'majors');
    }

    public function updatedFilters(): void {
        $this->dispatch('dashboardFiltersChanged', $this->filters);
        $this->dispatch('$refresh');
    }
}
