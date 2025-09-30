<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            {{ $this->getHeading() }}
        </x-slot>

        <div class="space-y-3">
            @if(empty($notifications))
            <div class="text-center py-8 text-gray-500">
                <x-heroicon-o-bell class="w-12 h-12 mx-auto mb-4 text-gray-300" />
                <p>Không có thông báo mới</p>
            </div>
            @else
            @foreach($notifications as $notification)
            <div class="flex items-start space-x-3 p-3 rounded-lg border 
                        @if($notification['color'] === 'success') border-green-200 bg-green-50
                        @elseif($notification['color'] === 'warning') border-yellow-200 bg-yellow-50
                        @elseif($notification['color'] === 'info') border-blue-200 bg-blue-50
                        @else border-gray-200 bg-gray-50 @endif">

                <div class="flex-shrink-0">
                    @if($notification['color'] === 'success')
                    <x-heroicon-o-check-circle class="w-5 h-5 text-green-600" />
                    @elseif($notification['color'] === 'warning')
                    <x-heroicon-o-exclamation-triangle class="w-5 h-5 text-yellow-600" />
                    @elseif($notification['color'] === 'info')
                    <x-heroicon-o-information-circle class="w-5 h-5 text-blue-600" />
                    @else
                    <x-heroicon-o-bell class="w-5 h-5 text-gray-600" />
                    @endif
                </div>

                <div class="flex-1 min-w-0">
                    <div class="flex items-center justify-between">
                        <h4 class="text-sm font-medium text-gray-900">
                            {{ $notification['title'] }}
                        </h4>
                        <span class="text-xs text-gray-500">
                            {{ $notification['time'] }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-600 mt-1">
                        {{ $notification['message'] }}
                    </p>
                </div>

                <div class="flex-shrink-0">
                    <button
                        wire:click="markAsRead('{{ $notification['type'] }}')"
                        class="text-gray-400 hover:text-gray-600 transition-colors"
                        title="Đánh dấu đã đọc">
                        <x-heroicon-o-x-mark class="w-4 h-4" />
                    </button>
                </div>
            </div>
            @endforeach

            <div class="flex justify-end pt-3 border-t">
                <button
                    wire:click="clearAll"
                    class="text-sm text-gray-500 hover:text-gray-700 transition-colors">
                    Xóa tất cả
                </button>
            </div>
            @endif
        </div>
    </x-filament::section>
</x-filament-widgets::widget>