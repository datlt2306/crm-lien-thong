<div class="px-3 py-3 border-t border-gray-100 dark:border-white/5">
    <form action="{{ filament()->getLogoutUrl() }}" method="post">
        @csrf
        <button type="submit" class="flex w-full items-center gap-x-3 px-3 py-2 text-sm font-medium rounded-lg text-gray-700 dark:text-gray-200 hover:bg-gray-100 dark:hover:bg-white/5 transition-all duration-75 group">
            <svg class="w-5 h-5 text-gray-400 group-hover:text-red-500 transition-colors" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0013.5 3h-6a2.25 2.25 0 00-2.25 2.25v13.5A2.25 2.25 0 007.5 21h6a2.25 2.25 0 002.25-2.25V15m3 0l3-3m0 0l-3-3m3 3H9" />
            </svg>
            <span class="group-hover:text-red-600">Đăng xuất</span>
        </button>
    </form>
</div>
