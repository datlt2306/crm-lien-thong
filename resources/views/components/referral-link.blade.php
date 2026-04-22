@php
    $user = auth()->user();
    $refId = $user?->collaborator?->ref_id;
    $refUrl = $refId ? url("/ref/{$refId}") : null;
@endphp

@if($refUrl)
<div x-data="{ 
    copyLink() {
        window.navigator.clipboard.writeText('{{ $refUrl }}');
        new FilamentNotification()
            .title('Đã copy link giới thiệu!')
            .success()
            .send();
    }
}" class="flex items-center px-1">
    <button
        x-on:click="copyLink()"
        type="button"
        class="p-2 text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200 focus:outline-none rounded-full transition-colors"
        title="Copy link giới thiệu: {{ $refUrl }}"
    >
        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.684 13.342C8.886 12.938 9 12.482 9 12c0-.482-.114-.938-.316-1.342m0 2.684a3 3 0 110-2.684m0 2.684l6.632 3.316m-6.632-6l6.632-3.316m0 0a3 3 0 105.367-2.684 3 3 0 00-5.367 2.684zm0 9.316a3 3 0 105.368 2.684 3 3 0 00-5.368-2.684z"></path>
        </svg>
    </button>
</div>
@endif
