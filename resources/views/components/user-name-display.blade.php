@php
    $user = \Illuminate\Support\Facades\Auth::user();
    $userName = $user->name ?? '';
    $role = $user->role ?? 'user';

    $roleLabels = [
        'super_admin' => 'Super Admin',
        'admin' => 'Admin',
        'organization_owner' => 'Chủ đơn vị',
        'accountant' => 'Kế toán',
        'ctv' => 'Cộng tác viên',
        'user' => 'Người dùng',
    ];

    $label = $roleLabels[$role] ?? $role;
    $badgeColors = 'text-green-500 border-green-500/40 bg-green-500/10';
@endphp

<div class="hidden md:flex items-center px-3 border-r border-gray-200 dark:border-gray-700">
    <div class="flex items-center gap-2">
        <span class="text-sm font-bold text-black dark:text-white whitespace-nowrap">{{ $userName }}</span>
        <span class="text-gray-300 dark:text-gray-600">-</span>
        <span class="px-2 py-1 font-semibold rounded-full border {{ $badgeColors }} whitespace-nowrap" style="font-size: 10px !important; line-height: 1 !important;">
            {{ $label }}
        </span>
    </div>
</div>
