<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Header -->
        <div class="flex items-center justify-between">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-gray-100">
                    Tất cả thông báo
                </h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                    Quản lý và xem tất cả thông báo của bạn
                </p>
            </div>
            
            @if($this->unreadCount > 0)
                <x-filament::button
                    wire:click="markAllAsRead"
                    color="primary"
                    size="sm"
                >
                    Đánh dấu tất cả đã đọc ({{ $this->unreadCount }})
                </x-filament::button>
            @endif
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-blue-100 dark:bg-blue-900/20 rounded-lg">
                        <x-heroicon-o-bell class="h-6 w-6 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Tổng thông báo</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $this->notifications->total() }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-red-100 dark:bg-red-900/20 rounded-lg">
                        <x-heroicon-o-exclamation-circle class="h-6 w-6 text-red-600 dark:text-red-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Chưa đọc</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $this->unreadCount }}</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
                <div class="flex items-center">
                    <div class="p-2 bg-green-100 dark:bg-green-900/20 rounded-lg">
                        <x-heroicon-o-check-circle class="h-6 w-6 text-green-600 dark:text-green-400" />
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Đã đọc</p>
                        <p class="text-2xl font-bold text-gray-900 dark:text-gray-100">{{ $this->notifications->total() - $this->unreadCount }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Notifications List -->
        <div class="bg-white dark:bg-gray-800 rounded-lg shadow">
            @if($this->notifications->isEmpty())
                <div class="p-12 text-center">
                    <x-heroicon-o-bell class="mx-auto h-12 w-12 text-gray-400 dark:text-gray-500 mb-4" />
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-2">
                        Không có thông báo
                    </h3>
                    <p class="text-gray-600 dark:text-gray-400">
                        Bạn sẽ nhận được thông báo khi có sự kiện mới trong hệ thống.
                    </p>
                </div>
            @else
                <div class="divide-y divide-gray-200 dark:divide-gray-700">
                    @foreach($this->notifications as $notification)
                        @php
                            $icon = match($notification->data['icon'] ?? '') {
                                'heroicon-o-check-circle' => 'heroicon-o-check-circle',
                                'heroicon-o-x-circle' => 'heroicon-o-x-circle',
                                'heroicon-o-currency-dollar' => 'heroicon-o-currency-dollar',
                                'heroicon-o-exclamation-triangle' => 'heroicon-o-exclamation-triangle',
                                default => 'heroicon-o-bell'
                            };
                            $color = match($notification->data['color'] ?? '') {
                                'success' => 'text-green-600 dark:text-green-400',
                                'danger' => 'text-red-600 dark:text-red-400',
                                'warning' => 'text-yellow-600 dark:text-yellow-400',
                                'info' => 'text-blue-600 dark:text-blue-400',
                                default => 'text-gray-600 dark:text-gray-400'
                            };
                            $bgColor = $notification->read_at ? 'bg-white dark:bg-gray-800' : 'bg-blue-50 dark:bg-blue-900/20';
                        @endphp
                        
                        <div class="p-6 {{ $bgColor }} hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors duration-200"
                             @if(!$notification->read_at) style="border-left: 4px solid #3b82f6;" @endif>
                            <div class="flex items-start space-x-4">
                                <div class="flex-shrink-0">
                                    <div class="p-2 rounded-lg {{ str_replace('text-', 'bg-', str_replace('-600', '-100', $color)) }} dark:bg-gray-700">
                                        <x-dynamic-component :component="$icon" class="h-5 w-5 {{ $color }}" />
                                    </div>
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <h4 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $notification->data['title'] ?? 'Thông báo' }}
                                        </h4>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $notification->created_at->diffForHumans() }}
                                            </span>
                                            @if(!$notification->read_at)
                                                <x-filament::button
                                                    wire:click="markAsRead('{{ $notification->id }}')"
                                                    size="xs"
                                                    color="gray"
                                                >
                                                    Đánh dấu đã đọc
                                                </x-filament::button>
                                            @endif
                                        </div>
                                    </div>
                                    
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-2">
                                        {{ $notification->data['body'] ?? '' }}
                                    </p>
                                    
                                    @if(isset($notification->data['data']) && is_array($notification->data['data']))
                                        <div class="mt-3 text-xs text-gray-500 dark:text-gray-400">
                                            @if(isset($notification->data['data']['payment_id']))
                                                <span class="inline-flex items-center px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200">
                                                    Payment ID: {{ $notification->data['data']['payment_id'] }}
                                                </span>
                                            @endif
                                            @if(isset($notification->data['data']['amount']))
                                                <span class="inline-flex items-center px-2 py-1 rounded-full bg-gray-100 dark:bg-gray-700 text-gray-800 dark:text-gray-200 ml-2">
                                                    {{ number_format($notification->data['data']['amount'], 0, ',', '.') }} VNĐ
                                                </span>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                                
                                @if(!$notification->read_at)
                                    <div class="flex-shrink-0">
                                        <div class="h-3 w-3 bg-blue-600 rounded-full"></div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Pagination -->
                @if($this->notifications->hasPages())
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700">
                        {{ $this->notifications->links() }}
                    </div>
                @endif
            @endif
        </div>
    </div>
</x-filament-panels::page>
