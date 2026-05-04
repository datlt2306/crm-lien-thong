<div class="flex flex-wrap items-end gap-3" x-data="{ range: @entangle('filters.range') }">
    <div class="flex flex-col">
        <label class="text-sm text-gray-600">Khoảng thời gian</label>
        <select wire:model.live="filters.range" wire:key="filter-range" class="fi-input w-48">
            <option value="today">Hôm nay</option>
            <option value="last_7_days">7 ngày</option>
            <option value="last_30_days">30 ngày</option>
            <option value="this_month">Tháng này</option>
            <option value="custom">Tùy chọn</option>
        </select>
    </div>
    <div class="flex flex-col" x-show="range === 'custom'">
        <label class="text-sm text-gray-600">Từ ngày</label>
        <input type="date" wire:model.live="filters.from" wire:key="filter-from" class="fi-input" />
    </div>
    <div class="flex flex-col" x-show="range === 'custom'">
        <label class="text-sm text-gray-600">Đến ngày</label>
        <input type="date" wire:model.live="filters.to" wire:key="filter-to" class="fi-input" />
    </div>
</div>