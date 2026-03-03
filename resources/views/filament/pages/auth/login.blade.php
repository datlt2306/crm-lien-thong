<x-filament-panels::page.simple>
    <div class="mb-6 text-center">
        <h2 class="text-2xl font-bold tracking-tight text-stone-900 dark:text-white">
            Đăng nhập hệ thống CRM
        </h2>
        <p class="mt-2 text-sm text-stone-600 dark:text-stone-400">
            Hệ thống quản lý, theo dõi tiến độ và hỗ trợ hiệu quả nhất.
        </p>
    </div>

    {{ $this->content }}

    <!-- Trust & Authority Badges -->
    <div class="mt-8 pt-6 border-t border-stone-200 dark:border-white/10 flex flex-col items-center">
        <div class="flex items-center justify-center space-x-6 text-stone-400 dark:text-stone-500">
            <div class="flex flex-col items-center justify-center space-y-2 group cursor-pointer">
                <div class="p-3 rounded-xl bg-stone-100 dark:bg-stone-900 group-hover:bg-yellow-100 dark:group-hover:bg-yellow-900/30 transition-colors duration-200">
                    <svg class="w-6 h-6 text-stone-400 group-hover:text-yellow-600 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z" />
                    </svg>
                </div>
                <span class="text-xs font-medium group-hover:text-stone-900 dark:group-hover:text-stone-300 transition-colors duration-200">Bảo mật cao</span>
            </div>
            
            <div class="flex flex-col items-center justify-center space-y-2 group cursor-pointer">
                <div class="p-3 rounded-xl bg-stone-100 dark:bg-stone-900 group-hover:bg-yellow-100 dark:group-hover:bg-yellow-900/30 transition-colors duration-200">
                    <svg class="w-6 h-6 text-stone-400 group-hover:text-yellow-600 transition-colors duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                    </svg>
                </div>
                <span class="text-xs font-medium group-hover:text-stone-900 dark:group-hover:text-stone-300 transition-colors duration-200">Mã hóa SSL</span>
            </div>
            
            <div class="flex flex-col items-center justify-center space-y-2 group cursor-pointer">
                <div class="p-3 rounded-xl bg-stone-100 dark:bg-stone-900 group-hover:bg-yellow-100 dark:group-hover:bg-yellow-900/30 transition-colors duration-200">
                     <svg class="w-6 h-6 text-stone-400 group-hover:text-yellow-600 transition-colors duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path>
                    </svg>
                </div>
                <span class="text-xs font-medium group-hover:text-stone-900 dark:group-hover:text-stone-300 transition-colors duration-200">Hiệu suất tốc độ</span>
            </div>
        </div>
    </div>
</x-filament-panels::page.simple>
