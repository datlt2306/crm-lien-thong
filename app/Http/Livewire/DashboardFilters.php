<?php

namespace App\Http\Livewire;

use Livewire\Component;


class DashboardFilters extends Component {
    public array $filters = [
        'range' => 'last_30_days',
        'from' => null,
        'to' => null,
        'program_type' => null,
        'major' => null,
    ];

    protected $listeners = [
        'setDashboardFilters' => 'setFilters',
    ];

    public function setFilters(array $filters): void {
        $this->filters = array_merge($this->filters, $filters);
        $this->emitUp('dashboardFiltersChanged', $this->filters);
        $this->dispatch('$refresh');
    }

    public function updatedFilters(): void {
        $this->emit('dashboardFiltersChanged', $this->filters);
        $this->dispatch('$refresh');
    }

    public function render() {
        $organizations = collect();
        $majors = \Illuminate\Support\Facades\DB::table('quotas')
            ->select('major_name as id', 'major_name as name')
            ->whereNotNull('major_name')
            ->distinct()
            ->orderBy('major_name')
            ->get();
        return view('livewire.dashboard-filters', compact('organizations', 'majors'));
    }
}
