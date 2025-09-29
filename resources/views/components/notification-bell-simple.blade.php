@php
$user = auth()->user();
$notifications = $user ? $user->notifications()->latest()->limit(5)->get() : collect();
$unreadCount = $user ? $user->unreadNotifications()->count() : 0;
@endphp

<div x-data="{ open: false }" @click.outside="open = false" style="position: relative;">
    <button
        @click="open = !open"
        style="position: relative; padding: 0.5rem; color: #9ca3af; transition: color 0.2s; border-radius: 0.375rem; background: none; border: none; cursor: pointer;"
        onmouseover="this.style.color='#d1d5db'"
        onmouseout="this.style.color='#9ca3af'"
        title="Thông báo">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v2H4a2 2 0 01-2-2V7a2 2 0 012-2h3.293l.707-.707A1 1 0 019.293 4h5.414a1 1 0 01.707.293L16 5h3a2 2 0 012 2v7a2 2 0 01-2 2h-2v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 012-2z"></path>
        </svg>

        @if($unreadCount > 0)
        <span style="position: absolute; top: -0.25rem; right: -0.25rem; display: inline-flex; align-items: center; justify-content: center; padding: 0.125rem 0.375rem; font-size: 0.75rem; font-weight: bold; line-height: 1; color: white; transform: translate(50%, -50%); background-color: #dc2626; border-radius: 9999px; animation: pulse-red 2s infinite;">
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
        style="position: absolute; right: 0; z-index: 50; margin-top: 0.5rem; width: 20rem; background: white; border-radius: 0.375rem; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05); border: 1px solid rgba(0, 0, 0, 0.1);"
        style="display: none;">
        <!-- Header -->
        <div style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb;">
            <div style="display: flex; align-items: center; justify-content: space-between;">
                <h3 style="font-size: 0.875rem; font-weight: 500; color: #111827;">
                    Thông báo
                </h3>
                @if($unreadCount > 0)
                <button
                    onclick="markAllAsRead()"
                    style="font-size: 0.75rem; color: #2563eb; transition: color 0.2s; background: none; border: none; cursor: pointer;"
                    onmouseover="this.style.color='#1d4ed8'"
                    onmouseout="this.style.color='#2563eb'">
                    Đánh dấu tất cả đã đọc
                </button>
                @endif
            </div>
        </div>

        <!-- Notifications List -->
        <div style="max-height: 24rem; overflow-y: auto;">
            @if($notifications->isEmpty())
            <div style="padding: 2rem 1rem; text-align: center; color: #6b7280;">
                <svg class="mx-auto h-8 w-8 mb-2" style="color: #9ca3af;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-5 5v-5zM4 19h6v2H4a2 2 0 01-2-2V7a2 2 0 012-2h3.293l.707-.707A1 1 0 019.293 4h5.414a1 1 0 01.707.293L16 5h3a2 2 0 012 2v7a2 2 0 01-2 2h-2v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 012-2z"></path>
                </svg>
                <p style="font-size: 0.875rem;">Không có thông báo mới</p>
                <p style="font-size: 0.75rem; margin-top: 0.25rem;">Bạn sẽ nhận được thông báo khi có sự kiện mới</p>
            </div>
            @else
            @foreach($notifications as $notification)
            @php
            $icon = match($notification->data['icon'] ?? '') {
            'heroicon-o-check-circle' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
            'heroicon-o-x-circle' => 'M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z',
            'heroicon-o-currency-dollar' => 'M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1',
            'heroicon-o-exclamation-triangle' => 'M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z',
            default => 'M15 17h5l-5 5v-5zM4 19h6v2H4a2 2 0 01-2-2V7a2 2 0 012-2h3.293l.707-.707A1 1 0 019.293 4h5.414a1 1 0 01.707.293L16 5h3a2 2 0 012 2v7a2 2 0 01-2 2h-2v2a2 2 0 01-2 2H4a2 2 0 01-2-2v-2a2 2 0 012-2z'
            };
            $color = match($notification->data['color'] ?? '') {
            'success' => '#16a34a',
            'danger' => '#dc2626',
            'warning' => '#d97706',
            'info' => '#2563eb',
            default => '#6b7280'
            };
            $bgColor = $notification->read_at ? '#f9fafb' : '#eff6ff';
            @endphp

            <div
                style="padding: 0.75rem 1rem; border-bottom: 1px solid #e5e7eb; transition: background-color 0.2s; background-color: {{ $bgColor }};"
                onmouseover="this.style.backgroundColor='#f3f4f6'"
                onmouseout="this.style.backgroundColor='{{ $bgColor }}'"
                data-notification-id="{{ $notification->id }}"
                data-read="{{ $notification->read_at ? 'true' : 'false' }}">
                <div style="display: flex; align-items: flex-start; gap: 0.75rem;">
                    <div style="flex-shrink: 0;">
                        <svg class="h-5 w-5" style="color: {{ $color }};" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $icon }}"></path>
                        </svg>
                    </div>

                    <div style="flex: 1; min-width: 0;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <p style="font-size: 0.875rem; font-weight: 500; color: #111827;">
                                {{ $notification->data['title'] ?? 'Thông báo' }}
                            </p>
                            @if(!$notification->read_at)
                            <button
                                onclick="markAsRead('{{ $notification->id }}')"
                                style="font-size: 0.75rem; color: #2563eb; transition: color 0.2s; background: none; border: none; cursor: pointer;"
                                onmouseover="this.style.color='#1d4ed8'"
                                onmouseout="this.style.color='#2563eb'">
                                Đánh dấu đã đọc
                            </button>
                            @endif
                        </div>
                        <p style="font-size: 0.875rem; color: #6b7280; margin-top: 0.25rem;">
                            {{ $notification->data['body'] ?? '' }}
                        </p>
                        <p style="font-size: 0.75rem; color: #9ca3af; margin-top: 0.25rem;">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>

                    @if(!$notification->read_at)
                    <div style="flex-shrink: 0;">
                        <div style="height: 0.5rem; width: 0.5rem; background-color: #2563eb; border-radius: 9999px; animation: pulse-red 2s infinite;"></div>
                    </div>
                    @endif
                </div>
            </div>
            @endforeach
            @endif
        </div>

        <!-- Footer -->
        <div style="padding: 0.75rem 1rem; border-top: 1px solid #e5e7eb;">
            <a
                href="{{ route('filament.admin.resources.notification-preferences.index') }}"
                style="display: block; text-align: center; font-size: 0.875rem; color: #2563eb; transition: color 0.2s; text-decoration: none;"
                onmouseover="this.style.color='#1d4ed8'"
                onmouseout="this.style.color='#2563eb'">
                Xem tất cả thông báo
            </a>
        </div>
    </div>
</div>

<style>
    @keyframes pulse-red {

        0%,
        100% {
            opacity: 1;
        }

        50% {
            opacity: 0.5;
        }
    }

    @media (max-width: 768px) {
        .notification-dropdown {
            width: 18rem !important;
            right: -1rem !important;
        }
    }
</style>

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
                        notificationElement.style.backgroundColor = '#f9fafb';

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
                        element.style.backgroundColor = '#f9fafb';

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
        const badge = document.querySelector('.notification-badge');

        if (unreadElements.length === 0) {
            if (badge) {
                badge.remove();
            }
        } else {
            if (!badge) {
                const button = document.querySelector('button[title="Thông báo"]');
                if (button) {
                    const newBadge = document.createElement('span');
                    newBadge.className = 'notification-badge';
                    newBadge.style.cssText = 'position: absolute; top: -0.25rem; right: -0.25rem; display: inline-flex; align-items: center; justify-content: center; padding: 0.125rem 0.375rem; font-size: 0.75rem; font-weight: bold; line-height: 1; color: white; transform: translate(50%, -50%); background-color: #dc2626; border-radius: 9999px; animation: pulse-red 2s infinite;';
                    newBadge.textContent = unreadElements.length > 99 ? '99+' : unreadElements.length;
                    button.appendChild(newBadge);
                }
            } else {
                badge.textContent = unreadElements.length > 99 ? '99+' : unreadElements.length;
            }
        }
    }
</script>