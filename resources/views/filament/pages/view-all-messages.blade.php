<x-filament-panels::page>
    <div class="space-y-4">
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 rounded-lg">
                        <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Tổng tin nhắn</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $this->getMessages()->total() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 rounded-lg">
                        <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Chưa đọc</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $this->getUnreadCount() }}</p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg border p-4">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 rounded-lg">
                        <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-500">Đã đọc</p>
                        <p class="text-2xl font-semibold text-gray-900">{{ $this->getMessages()->total() - $this->getUnreadCount() }}</p>
                    </div>
                </div>
            </div>
        </div>

        @if($this->getMessages()->count() > 0)
        <div class="grid gap-4">
            @foreach($this->getMessages() as $notification)
            <div class="bg-white rounded-lg border p-4 {{ $notification->read_at ? '' : 'border-blue-200 bg-blue-50' }}">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-2">
                            @if(!$notification->read_at)
                            <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                            @endif
                            <h3 class="font-medium text-gray-900">
                                {{ $notification->data['title'] ?? 'Tin nhắn' }}
                            </h3>
                            <span class="px-2 py-1 text-xs font-medium rounded-full {{ $notification->read_at ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800' }}">
                                {{ $notification->read_at ? 'Đã đọc' : 'Chưa đọc' }}
                            </span>
                        </div>

                        <p class="text-gray-600 mb-3">
                            {{ $notification->data['body'] ?? 'Nội dung tin nhắn' }}
                        </p>

                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500">
                                {{ $notification->created_at->format('d/m/Y H:i') }}
                                ({{ $notification->created_at->diffForHumans() }})
                            </span>

                            <div class="flex gap-2">
                                @if(!$notification->read_at)
                                <button
                                    wire:click="markAsRead('{{ $notification->id }}')"
                                    class="text-sm text-blue-600 hover:text-blue-800 font-medium px-3 py-1 rounded-md hover:bg-blue-100">
                                    Đánh dấu đã đọc
                                </button>
                                @endif

                                <button
                                    wire:click="deleteMessage('{{ $notification->id }}')"
                                    wire:confirm="Bạn có chắc chắn muốn xóa tin nhắn này?"
                                    class="text-sm text-red-600 hover:text-red-800 font-medium px-3 py-1 rounded-md hover:bg-red-100">
                                    Xóa
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <div class="mt-6">
            {{ $this->getMessages()->links() }}
        </div>
        @else
        <div class="text-center py-12">
            <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path>
            </svg>
            <h3 class="mt-2 text-sm font-medium text-gray-900">Không có tin nhắn</h3>
            <p class="mt-1 text-sm text-gray-500">Bạn chưa có tin nhắn nào.</p>
        </div>
        @endif
    </div>
</x-filament-panels::page>