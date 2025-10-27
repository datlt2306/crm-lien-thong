@php
$user = \Illuminate\Support\Facades\Auth::user();
$userName = $user->name ?? '';
@endphp

<div class="hidden md:flex items-center px-3 border-r border-gray-200 dark:border-gray-700">
    <div class="flex items-center gap-2">
        <span class="text-sm font-semibold text-gray-900 dark:text-gray-100">{{ $userName }}</span>
    </div>
</div>

