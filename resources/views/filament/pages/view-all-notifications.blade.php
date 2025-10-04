<x-filament-panels::page>
    <div class="space-y-4">
        @if($this->getNotifications()->count() > 0)
        <div class="grid gap-4">
            @foreach($this->getNotifications() as $notification)
            <div class="bg-white rounded-lg border p-4 {{ $notification->read_at ? '' : 'border-blue-200 bg-blue-50' }}">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center gap-2">
                            @if(!$notification->read_at)
                            <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                            @endif
                            <h3 class="font-medium text-gray-900">
                                {{ $notification->data['title'] ?? 'Thông báo' }}
                            </h3>
                        </div>

                        <p class="text-gray-600 mt-2">
                            {{ $notification->data['body'] ?? 'Nội dung thông báo' }}
                        </p>

                        <div class="flex items-center justify-between mt-3">
                            <span class="text-sm text-gray-500">
                                {{ $notification->created_at->format('d/m/Y H:i') }}
                                ({{ $notification->created_at->diffForHumans() }})
                            </span>

                            @if(!$notification->read_at)
                            <button
                                wire:click="markAsRead('{{ $notification->id }}')"
                                class="text-sm text-blue-600 hover:text-blue-800 font-medium">
                                Đánh dấu đã đọc
                            </button>
                            @else
                            <span class="text-sm text-green-600 font-medium">
                                Đã đọc
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $this->getNotifications()->links() }}
        </div>
        @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Không có thông báo</h3>
            <p class="mt-1 text-sm text-gray-500">Bạn chưa có thông báo nào.</p>
        </div>
        @endif
    </div>
</x-filament-panels::page>