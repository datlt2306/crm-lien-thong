<div class="dashboard-filters-widget" x-data="{ range: @entangle('filters.range') }" wire:ignore.self>
    <div class="flex items-center gap-2 mr-2 text-primary-600 dark:text-primary-400">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" class="w-4 h-4">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
        </svg>
        <span class="text-[10px] font-black uppercase tracking-widest opacity-70">Lọc</span>
    </div>

    <div class="flex items-center gap-3">
        <div class="flex flex-col">
            <select wire:model.live="filters.range" wire:key="filter-range" class="fi-input-compact">
                <option value="today">Hôm nay</option>
                <option value="last_7_days">7 ngày</option>
                <option value="last_30_days">30 ngày</option>
                <option value="this_month">Tháng này</option>
                <option value="custom">Tùy chọn...</option>
            </select>
        </div>
        
        <div class="flex items-center gap-2" x-show="range === 'custom'" x-cloak x-transition>
            <input type="date" wire:model.live="filters.from" wire:key="filter-from" class="fi-input-compact w-32" />
            <span class="text-gray-400">→</span>
            <input type="date" wire:model.live="filters.to" wire:key="filter-to" class="fi-input-compact w-32" />
        </div>
    </div>
</div>