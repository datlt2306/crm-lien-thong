@php
    $user = \Illuminate\Support\Facades\Auth::user();
    $userName = $user->name ?? '';
    $role = $user->role ?? 'user';

    $roleLabels = [
        'super_admin' => 'Quản trị viên',
        'admin' => 'Quản trị viên',
        'organization_owner' => 'Chủ đơn vị',
        'accountant' => 'Kế toán',
        'admissions' => 'Cán bộ tuyển sinh',
        'document' => 'Cán bộ hồ sơ',
        'ctv' => 'Cộng tác viên',
        'user' => 'Người dùng',
    ];

    $label = $roleLabels[$role] ?? $role;
    $badgeColors = 'text-green-500 border-green-500/40 bg-green-500/10';
@endphp

<div class="hidden md:flex items-center px-3 border-r border-gray-200 dark:border-gray-700">
    <div class="flex items-center gap-2">
        <span class="text-sm font-bold text-slate-900 dark:text-white whitespace-nowrap">{{ $userName }}</span>
        <span class="text-gray-400 dark:text-gray-600">-</span>
        <span class="px-2 py-1 font-semibold rounded-full border {{ $badgeColors }} whitespace-nowrap text-[10px] leading-none">
            {{ $label }}
        </span>
    </div>
</div>
