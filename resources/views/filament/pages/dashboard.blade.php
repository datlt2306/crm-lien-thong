<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header Widgets -->
        @if ($this->getHeaderWidgets())
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2 xl:grid-cols-4">
            @foreach ($this->getHeaderWidgets() as $widget)
            @livewire($widget)
            @endforeach
        </div>
        @endif

        <!-- Chart Widgets -->
        <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
            @foreach ($this->getFooterWidgets() as $widget)
            <div class="bg-white rounded-lg shadow p-6">
                @livewire($widget)
            </div>
            @endforeach
        </div>
    </div>
</x-filament-panels::page>