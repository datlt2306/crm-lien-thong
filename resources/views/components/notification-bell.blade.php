@php
$user = auth()->user();
$notifications = $user ? $user->notifications()->latest()->limit(5)->get() : collect();
$unreadCount = $user ? $user->unreadNotifications()->count() : 0;
@endphp

<style>
    /* Inline styles for notification bell */
    .notification-bell-container {
        position: relative;
    }

    .notification-bell-button {
        position: relative;
        padding: 0.5rem;
        color: #9ca3af;
        transition: color 0.2s;
        border-radius: 0.375rem;
    }

    .notification-bell-button:hover {
        color: #d1d5db;
    }

    .notification-bell-button:focus {
        outline: none;
        ring: 2px;
        ring-color: #f59e0b;
        ring-inset: true;
    }

    .notification-bell-icon {
        height: 1.25rem;
        width: 1.25rem;
    }

    .notification-badge {
        position: absolute;
        top: -0.25rem;
        right: -0.25rem;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.125rem 0.375rem;
        font-size: 0.75rem;
        font-weight: bold;
        line-height: 1;
        color: white;
        transform: translate(50%, -50%);
        background-color: #dc2626;
        border-radius: 9999px;
        animation: pulse-red 2s infinite;
    }

    @keyframes pulse-red {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    .notification-dropdown {
        position: absolute;
        right: 0;
        z-index: 50;
        margin-top: 0.5rem;
        width: 20rem;
        background: white;
        border-radius: 0.375rem;
        box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
        border: 1px solid rgba(0, 0, 0, 0.1);
    }

    .dark .notification-dropdown {
        background: #1f2937;
        border-color: #374151;
    }

    .notification-header {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e5e7eb;
    }

    .dark .notification-header {
        border-bottom-color: #374151;
    }

    .notification-list {
        max-height: 24rem;
        overflow-y: auto;
    }

    .notification-item {
        padding: 0.75rem 1rem;
        border-bottom: 1px solid #e5e7eb;
        transition: background-color 0.2s;
    }

    .dark .notification-item {
        border-bottom-color: #374151;
    }

    .notification-item:hover {
        background-color: #f9fafb;
    }

    .dark .notification-item:hover {
        background-color: #374151;
    }

    .notification-item-unread {
        background-color: #eff6ff;
    }

    .dark .notification-item-unread {
        background-color: rgba(59, 130, 246, 0.1);
    }

    .notification-footer {
        padding: 0.75rem 1rem;
        border-top: 1px solid #e5e7eb;
    }

    .dark .notification-footer {
        border-top-color: #374151;
    }

    @media (max-width: 768px) {
        .notification-dropdown {
            width: 18rem;
            right: -1rem;
        }
    }
</style>

<div x-data="{ open: false }" @click.outside="open = false" class="notification-bell-container">
    <button
        @click="open = !open"
        class="notification-bell-button"
        title="Thông báo">
        <x-heroicon-o-bell class="notification-bell-icon" />

        @if($unreadCount > 0)
        <span class="notification-badge">
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
        class="notification-dropdown"
        style="display: none;">
        <!-- Header -->
        <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
            <div class="flex items-center justify-between">
                <h3 class="text-sm font-medium text-gray-900 dark:text-gray-100">
                    Thông báo
                </h3>
                @if($unreadCount > 0)
                <button
                    onclick="markAllAsRead()"
                    class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200">
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
                <p class="text-xs mt-1">Bạn sẽ nhận được thông báo khi có sự kiện mới</p>
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

            <div class="px-4 py-3 border-b border-gray-200 dark:border-gray-700 hover:bg-gray-100 dark:hover:bg-gray-600 transition-colors duration-200 {{ $bgColor }}"
                data-notification-id="{{ $notification->id }}"
                data-read="{{ $notification->read_at ? 'true' : 'false' }}">
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
                                onclick="markAsRead('{{ $notification->id }}')"
                                class="text-xs text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200">
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
                        <div class="h-2 w-2 bg-blue-600 rounded-full animate-pulse"></div>
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
                class="block text-center text-sm text-blue-600 hover:text-blue-800 dark:text-blue-400 dark:hover:text-blue-300 transition-colors duration-200">
                Xem tất cả thông báo
            </a>
        </div>
    </div>
</div>

<script>
    function markAsRead(notificationId) {
        fetch('/admin/notifications/' + notificationId + '/mark-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                },
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update UI
                    const notificationElement = document.querySelector(`[data-notification-id="${notificationId}"]`);
                    if (notificationElement) {
                        notificationElement.setAttribute('data-read', 'true');
                        notificationElement.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');
                        notificationElement.classList.add('bg-gray-50', 'dark:bg-gray-700');

                        // Remove unread indicator
                        const unreadDot = notificationElement.querySelector('.animate-pulse');
                        if (unreadDot) {
                            unreadDot.remove();
                        }

                        // Remove mark as read button
                        const markAsReadBtn = notificationElement.querySelector('button');
                        if (markAsReadBtn) {
                            markAsReadBtn.remove();
                        }
                    }

                    // Update badge count
                    updateNotificationBadge();
                }
            }).catch(error => {
                console.error('Error marking notification as read:', error);
            });
    }

    function markAllAsRead() {
        fetch('/admin/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Content-Type': 'application/json',
                },
            }).then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update all notifications UI
                    const notificationElements = document.querySelectorAll('[data-notification-id]');
                    notificationElements.forEach(element => {
                        element.setAttribute('data-read', 'true');
                        element.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');
                        element.classList.add('bg-gray-50', 'dark:bg-gray-700');

                        const unreadDot = element.querySelector('.animate-pulse');
                        if (unreadDot) {
                            unreadDot.remove();
                        }

                        const markAsReadBtn = element.querySelector('button');
                        if (markAsReadBtn) {
                            markAsReadBtn.remove();
                        }
                    });

                    // Update badge count
                    updateNotificationBadge();
                }
            }).catch(error => {
                console.error('Error marking all notifications as read:', error);
            });
    }

    function updateNotificationBadge() {
        const unreadElements = document.querySelectorAll('[data-read="false"]');
        const badge = document.querySelector('.bg-red-600');

        if (unreadElements.length === 0) {
            if (badge) {
                badge.remove();
            }
        } else {
            if (!badge) {
                const button = document.querySelector('button[title="Thông báo"]');
                if (button) {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'absolute -top-1 -right-1 inline-flex items-center justify-center px-2 py-1 text-xs font-bold leading-none text-white transform translate-x-1/2 -translate-y-1/2 bg-red-600 rounded-full notification-badge';
                    newBadge.textContent = unreadElements.length > 99 ? '99+' : unreadElements.length;
                    button.appendChild(newBadge);
                }
            } else {
                badge.textContent = unreadElements.length > 99 ? '99+' : unreadElements.length;
            }
        }
    }
</script>