@php
$user = auth()->user();
$unreadCount = $user ? $user->unreadNotifications()->count() : 0;
$totalNotifications = $user ? $user->notifications()->count() : 0;
@endphp

<div class="relative" x-data="{ open: false }">
    <button
        @click="open = !open"
        class="relative p-2 text-gray-600 hover:text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 rounded-full">
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path>
        </svg>

        @if($unreadCount > 0)
        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full h-5 w-5 flex items-center justify-center">
            {{ $unreadCount > 99 ? '99+' : $unreadCount }}
        </span>
        @endif
    </button>

    <!-- Dropdown menu -->
    <div
        x-show="open"
        @click.away="open = false"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="transform opacity-0 scale-95"
        x-transition:enter-end="transform opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="transform opacity-100 scale-100"
        x-transition:leave-end="transform opacity-0 scale-95"
        class="absolute right-0 mt-2 w-80 bg-white rounded-md shadow-lg z-50 border"
        style="display: none;">
        <div class="p-4 border-b">
            <div class="flex justify-between items-center">
                <h3 class="text-lg font-medium text-gray-900">Thông báo</h3>
                @if($unreadCount > 0)
                <button
                    onclick="markAllAsRead()"
                    class="text-sm text-blue-600 hover:text-blue-800">
                    Đánh dấu tất cả đã đọc
                </button>
                @endif
            </div>
        </div>

        <div class="max-h-96 overflow-y-auto">
            @if($totalNotifications > 0)
            @foreach($user->notifications()->latest()->take(10)->get() as $notification)
            <div class="p-3 border-b hover:bg-gray-50 {{ $notification->read_at ? '' : 'bg-blue-50' }}">
                <div class="flex justify-between items-start">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-gray-900">
                            {{ $notification->data['title'] ?? 'Thông báo' }}
                        </p>
                        <p class="text-sm text-gray-600 mt-1">
                            {{ $notification->data['body'] ?? 'Nội dung thông báo' }}
                        </p>
                        <p class="text-xs text-gray-500 mt-1">
                            {{ $notification->created_at->diffForHumans() }}
                        </p>
                    </div>
                    @if(!$notification->read_at)
                    <button
                        onclick="markAsRead('{{ $notification->id }}')"
                        class="ml-2 text-blue-600 hover:text-blue-800 text-xs">
                        Đánh dấu đã đọc
                    </button>
                    @endif
                </div>
            </div>
            @endforeach
            @else
            <div class="p-6 text-center text-gray-500">
                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-gray-900">Không có thông báo</h3>
                <p class="mt-1 text-sm text-gray-500">Bạn chưa có thông báo nào.</p>
            </div>
            @endif
        </div>

        <div class="p-3 border-t text-center">
            <a
                href="{{ route('filament.admin.pages.view-all-notifications') }}"
                class="text-sm text-blue-600 hover:text-blue-800">
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
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
    }

    function markAllAsRead() {
        fetch('/admin/notifications/mark-all-read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    location.reload();
                }
            });
    }
</script>