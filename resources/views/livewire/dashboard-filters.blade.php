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
    <div class="flex flex-col">
        <label class="text-sm text-gray-600">Hệ đào tạo</label>
        <select wire:model.live="filters.program_type" wire:key="filter-program" class="fi-input w-40">
            <option value="">Tất cả</option>
            <option value="REGULAR">Chính quy</option>
            <option value="PART_TIME">Vừa học vừa làm</option>
            <option value="DISTANCE">Từ xa</option>
        </select>
    </div>
    <div class="flex flex-col">
        <label class="text-sm text-gray-600">Ngành</label>
        <select wire:model.live="filters.major" wire:key="filter-major" class="fi-input w-52">
            <option value="">Tất cả</option>
            @foreach($majors as $m)
            <option value="{{ $m->name }}" wire:key="major-{{ $m->id }}">{{ $m->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="flex flex-col">
        <label class="text-sm text-gray-600">Nhóm theo</label>
        <select wire:model.live="filters.group" wire:key="filter-group" class="fi-input w-40">
            <option value="day">Ngày</option>
            <option value="month">Tháng</option>
            <option value="year">Năm</option>
        </select>
    </div>
</div>