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
    ];

    protected function getViewData(): array {
        return [];
    }

    public function updated($name, $value): void {
        $this->dispatch('dashboardFiltersChanged', filters: $this->filters);
    }
}
