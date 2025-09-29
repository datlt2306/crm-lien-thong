<?php

namespace App\Filament\Widgets\Concerns;

trait WithDashboardFilters {
    public array $filters = [
        'range' => 'last_30_days',
        'from' => null,
        'to' => null,
        'program_type' => null,
        'organization_id' => null,
        'major' => null,
    ];

    protected function getListeners(): array {
        return [
            'dashboardFiltersChanged' => 'setFilters',
        ];
    }

    public function setFilters(array $filters): void {
        $this->filters = array_merge($this->filters, $filters);
        $this->dispatch('$refresh');
    }
}
