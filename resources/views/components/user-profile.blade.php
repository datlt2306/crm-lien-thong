@php
$user = \Illuminate\Support\Facades\Auth::user();
$userName = $user->name ?? '';
$userAvatar = $user->avatar ?? null;
$userInitials = strtoupper(substr($userName, 0, 1));
@endphp

<div class="flex items-center gap-3 px-4 py-3 border-b border-gray-200 dark:border-gray-700">
    @if($userAvatar)
    <img src="{{ \Illuminate\Support\Facades\Storage::url($userAvatar) }}"
        alt="{{ $userName }}"
        class="w-10 h-10 rounded-full object-cover">
    @else
    <div class="w-10 h-10 rounded-full bg-primary-500 flex items-center justify-center text-white font-semibold text-lg">
        {{ $userInitials }}
    </div>
    @endif
    <div class="flex flex-col">
        <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $userName }}</span>
        <span class="text-xs text-gray-500 dark:text-gray-400">{{ $user->email ?? '' }}</span>
    </div>
</div>