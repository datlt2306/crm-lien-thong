<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center justify-between">
                <span>Thông báo gần đây</span>
                @if($notifications->whereNull('read_at')->count() > 0)
                <x-filament::button
                    wire:click="markAllAsRead"
                    size="sm"
                    color="gray">
                    Đánh dấu tất cả đã đọc
                </x-filament::button>
                @endif
            </div>
        </x-slot>

        @if($notifications->isEmpty())
        <div class="text-center py-8 text-gray-500">
            <x-heroicon-o-bell class="mx-auto h-12 w-12 text-gray-400" />
            <h3 class="mt-2 text-sm font-medium text-gray-900">Không có thông báo</h3>
            <p class="mt-1 text-sm text-gray-500">Bạn sẽ nhận được thông báo khi có sự kiện mới.</p>
        </div>
        @else
        <div class="space-y-4">
            @foreach($notifications as $notification)
            <div class="flex items-start space-x-3 p-4 rounded-lg border 
                        {{ $notification->read_at ? 'bg-gray-50 border-gray-200' : 'bg-blue-50 border-blue-200' }}">
                <div class="flex-shrink-0">
                    @php
                    $icon = match($notification->data['icon'] ?? '') {
                    'heroicon-o-check-circle' => 'heroicon-o-check-circle',
                    'heroicon-o-x-circle' => 'heroicon-o-x-circle',
                    'heroicon-o-currency-dollar' => 'heroicon-o-currency-dollar',
                    'heroicon-o-exclamation-triangle' => 'heroicon-o-exclamation-triangle',
                    default => 'heroicon-o-bell'
                    };
                    $color = match($notification->data['color'] ?? '') {
                    'success' => 'text-green-600',
                    'danger' => 'text-red-600',
                    'warning' => 'text-yellow-600',
                    'info' => 'text-blue-600',
                    default => 'text-gray-600'
                    };
                    @endphp
                    <x-dynamic-component :component="$icon" class="h-5 w-5 {{ $color }}" />
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $notification->data['title'] ?? 'Thông báo' }}
                        </p>
                        @if(!$notification->read_at)
                        <button
                            wire:click="markAsRead('{{ $notification->id }}')"
                            class="text-xs text-blue-600 hover:text-blue-800">
                            Đánh dấu đã đọc
                        </button>
                        @endif
                    </div>
                    <p class="text-sm text-gray-600">
                        {{ $notification->data['body'] ?? '' }}
                    </p>
                    <p class="text-xs text-gray-500 mt-1">
                        {{ $notification->created_at->diffForHumans() }}
                    </p>
                </div>

                @if(!$notification->read_at)
                <div class="flex-shrink-0">
                    <div class="h-2 w-2 bg-blue-600 rounded-full"></div>
                </div>
                @endif
            </div>
            @endforeach
        </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>