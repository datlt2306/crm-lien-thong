@if($hasReceipt)
<div class="flex items-center gap-2">
    <a href="{{ $fileUrl }}"
        target="_blank"
        class="inline-flex items-center gap-1 px-2 py-1 text-xs font-medium text-blue-600 bg-blue-50 rounded-md hover:bg-blue-100 transition-colors">
        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
        </svg>
        Xem
    </a>
    <span class="text-xs text-gray-500 truncate max-w-20" title="{{ $fileName }}">
        {{ $fileName }}
    </span>
</div>
@else
<span class="text-gray-400">—</span>
@endif
