@php
    $unreadCount = $this->getUnreadCount();
@endphp

<div class="relative">
    <!-- Notification Bell Icon -->
    <button 
        wire:click="$refresh"
        class="relative p-2 text-gray-400 hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 rounded-md"
        title="Thông báo"
    >
        <x-heroicon-o-bell class="h-5 w-5" />
        
        @if($unreadCount > 0)
            <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                {{ $unreadCount > 99 ? '99+' : $unreadCount }}
            </span>
        @endif
    </button>

    <!-- Dropdown Menu -->
    <div 
        x-data="{ open: false }"
        @click.outside="open = false"
        class="relative"
    >
        <button 
            @click="open = !open"
            class="relative p-2 text-gray-400 hover:text-gray-300 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-primary-500 rounded-md"
        >
            <x-heroicon-o-bell class="h-5 w-5" />
            
            @if($unreadCount > 0)
                <span class="absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </button>

        <div 
            x-show="open"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            class="absolute right-0 z-50 mt-2 w-80 bg-white dark:bg-gray-800 rounded-md shadow-lg ring-1 ring-black ring-opacity-5 focus:outline-none"
            style="display: none;"
        >
            <!-- Header -->
            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
                <div class="flex items-center justify-between">
                    <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                        Thông báo
                    </h3>
                    @if($unreadCount > 0)
                        <button 
                            wire:click="markAllAsRead"
                            class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                        >
                            Đánh dấu tất cả đã đọc
                        </button>
                    @endif
                </div>
            </div>

            <!-- Notifications List -->
            <div class="max-h-96 overflow-y-auto">
                @if($notifications->isEmpty())
                    <div class="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                        <x-heroicon-o-bell class="mx-auto h-8 w-8 text-gray-400 dark:text-gray-500 mb-2" />
                        <p class="text-sm">Không có thông báo mới</p>
                    </div>
                @else
                    @foreach($notifications as $notification)
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
                            $bgColor = $notification->read_at ? 'bg-gray-50 dark:bg-gray-700' : 'bg-blue-50 dark:bg-blue-900/20';
                        @endphp
                        
                        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-700 {{ $bgColor }}">
                            <div class="flex items-start space-x-3">
                                <div class="flex-shrink-0">
                                    <x-dynamic-component :component="$icon" class="h-5 w-5 {{ $color }}" />
                                </div>
                                
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center justify-between">
                                        <p class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ $notification->data['title'] ?? 'Thông báo' }}
                                        </p>
                                        @if(!$notification->read_at)
                                            <button
                                                wire:click="markAsRead('{{ $notification->id }}')"
                                                class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                                            >
                                                Đánh dấu đã đọc
                                            </button>
                                        @endif
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-gray-300 mt-1">
                                        {{ $notification->data['body'] ?? '' }}
                                    </p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $notification->created_at->diffForHumans() }}
                                    </p>
                                </div>
                                
                                @if(!$notification->read_at)
                                    <div class="flex-shrink-0">
                                        <div class="h-2 w-2 bg-blue-600 rounded-full"></div>
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            <!-- Footer -->
            <div class="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
                <a 
                    href="{{ route('filament.admin.resources.notification-preferences.index') }}"
                    class="block text-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300"
                >
                    Xem tất cả thông báo
                </a>
            </div>
        </div>
    </div>
</div>
