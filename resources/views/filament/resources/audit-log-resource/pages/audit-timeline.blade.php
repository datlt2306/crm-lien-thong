<x-filament-panels::page>
    @push('styles')
        <link rel="stylesheet" href="{{ asset('css/audit-timeline.css') }}">
    @endpush
    <div class="space-y-6">
        {{-- Header Filters --}}
        <div class="flex flex-wrap gap-4 p-4 rounded-xl bg-white/50 dark:bg-gray-800/50 backdrop-blur-md border border-white/20 shadow-xl">
            <div class="flex-1 min-w-[200px]">
                {{ $this->form }}
            </div>
            <div class="flex items-end pb-1">
                <x-filament::button wire:click="applyFilters" icon="heroicon-m-funnel">
                    Lọc dữ liệu
                </x-filament::button>
            </div>
        </div>

        {{-- Timeline Container --}}
        <div class="relative overflow-hidden p-6 rounded-2xl bg-gray-50/30 dark:bg-gray-900/40 backdrop-blur-sm border border-gray-200/50 dark:border-gray-700/50 shadow-inner">
            <div class="absolute left-1/2 top-0 bottom-0 w-0.5 bg-gradient-to-b from-primary-500/50 via-primary-400/30 to-transparent hidden md:block"></div>

            <div class="space-y-12 relative">
                @forelse($this->getLogs() as $log)
                    <div class="relative flex flex-col md:flex-row items-center group">
                        {{-- Date Badge (Desktop Left) --}}
                        <div class="hidden md:flex md:w-5/12 justify-end pr-10 text-right">
                             <div class="space-y-1 opacity-60 group-hover:opacity-100 transition-opacity">
                                <span class="block text-sm font-bold text-gray-500 dark:text-gray-400 uppercase tracking-widest">
                                    {{ $log->created_at->format('M d, Y') }}
                                </span>
                                <span class="block text-xl font-mono text-primary-600 dark:text-primary-400">
                                    {{ $log->created_at->format('H:i:s') }}
                                </span>
                             </div>
                        </div>

                        {{-- Icon Node --}}
                        <div class="relative z-10 flex items-center justify-center w-12 h-12 rounded-full shadow-2xl transition-all duration-300 group-hover:scale-110
                            @if($log->event_group === 'FINANCIAL') bg-gradient-to-br from-amber-400 to-orange-600 ring-4 ring-amber-500/20
                            @elseif($log->event_group === 'SECURITY') bg-gradient-to-br from-rose-500 to-red-700 ring-4 ring-rose-500/20
                            @else bg-gradient-to-br from-cyan-400 to-blue-600 ring-4 ring-blue-500/20
                            @endif">
                            <x-filament::icon 
                                icon="{{ $log->event_group === 'FINANCIAL' ? 'heroicon-o-currency-dollar' : ($log->event_group === 'SECURITY' ? 'heroicon-o-shield-check' : 'heroicon-o-cog-6-tooth') }}" 
                                class="w-6 h-6 text-white" 
                            />
                        </div>

                        {{-- Content Card (Desktop Right) --}}
                        <div class="mt-4 md:mt-0 md:w-5/12 pl-0 md:pl-10">
                            <div class="timeline-content p-5 rounded-2xl border border-white/10 shadow-lg transition-all duration-300 hover:shadow-2xl hover:-translate-y-1 backdrop-blur-xl bg-white/70 dark:bg-gray-800/70">
                                <div class="flex justify-between items-start mb-3">
                                    <h3 class="font-bold text-lg text-gray-800 dark:text-gray-100 italic">
                                        {{ $log->event_type }}
                                    </h3>
                                    <span class="px-3 py-1 text-[10px] uppercase font-black rounded-full border 
                                        @if($log->event_group === 'FINANCIAL') border-amber-500/50 text-amber-600 bg-amber-50
                                        @elseif($log->event_group === 'SECURITY') border-rose-500/50 text-rose-600 bg-rose-50
                                        @else border-blue-500/50 text-blue-600 bg-blue-50
                                        @endif">
                                        {{ $log->event_group }}
                                    </span>
                                </div>

                                <div class="space-y-3 text-sm text-gray-600 dark:text-gray-300">
                                    <p class="flex items-center gap-2">
                                        <x-filament::icon icon="heroicon-m-user" class="w-4 h-4 text-primary-500" />
                                        <span class="font-semibold">{{ $log->user?->name ?? 'Hệ thống' }}</span>
                                        <span class="text-[10px] px-1.5 bg-gray-100 dark:bg-gray-700 rounded text-gray-500 uppercase">{{ $log->user_role }}</span>
                                    </p>

                                    @if($log->student)
                                        <p class="flex items-center gap-2">
                                            <x-filament::icon icon="heroicon-m-academic-cap" class="w-4 h-4 text-emerald-500" />
                                            <span class="opacity-70">Hồ sơ:</span>
                                            <a href="{{ \App\Filament\Resources\Students\StudentResource::getUrl('view', ['record' => $log->student_id]) }}" class="text-primary-600 underline decoration-dotted underline-offset-4 font-bold hover:text-primary-500">
                                                {{ $log->student->full_name }}
                                            </a>
                                        </p>
                                    @endif

                                    @if($log->reason)
                                        <div class="p-3 bg-gray-50/50 dark:bg-gray-700/50 rounded-xl border-l-4 border-primary-500 italic font-medium text-gray-700 dark:text-gray-200">
                                            "{{ $log->reason }}"
                                        </div>
                                    @endif

                                    @if(Auth::user()->role !== 'ctv')
                                        @if($log->old_values || $log->new_values)
                                            <div class="mt-4 pt-4 border-t border-gray-100 dark:border-gray-700 overflow-x-auto">
                                                <table class="w-full text-[11px] leading-tight">
                                                    <thead class="text-gray-400 font-normal">
                                                        <tr>
                                                            <th class="text-left pb-1 uppercase tracking-tighter">Trường</th>
                                                            <th class="text-left pb-1 uppercase tracking-tighter">Cũ</th>
                                                            <th class="text-left pb-1 uppercase tracking-tighter">Mới</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        @php
                                                            $allKeys = array_unique(array_merge(array_keys($log->old_values ?? []), array_keys($log->new_values ?? [])));
                                                        @endphp
                                                        @foreach($allKeys as $key)
                                                            @if(!in_array($key, ['updated_at', 'created_at', 'id']))
                                                                <tr class="border-b border-gray-50 dark:border-gray-800 last:border-0 hover:bg-white/50 transition-colors">
                                                                    <td class="py-1.5 font-bold text-gray-500">{{ $key }}</td>
                                                                    <td class="py-1.5 text-rose-500 line-through opacity-70">{{ is_array($log->old_values[$key] ?? '-') ? json_encode($log->old_values[$key]) : ($log->old_values[$key] ?? '-') }}</td>
                                                                    <td class="py-1.5 text-emerald-600 font-black tabular-nums">{{ is_array($log->new_values[$key] ?? '-') ? json_encode($log->new_values[$key]) : ($log->new_values[$key] ?? '-') }}</td>
                                                                </tr>
                                                            @endif
                                                        @endforeach
                                                    </tbody>
                                                </table>
                                            </div>
                                        @endif
                                        
                                        <div class="mt-4 flex justify-end">
                                            <x-filament::link 
                                                href="{{ \App\Filament\Resources\AuditLogResource::getUrl('view', ['record' => $log->id]) }}"
                                                icon="heroicon-m-eye"
                                                size="sm"
                                                color="primary"
                                                class="font-bold uppercase tracking-wider text-[11px]"
                                            >
                                                Xem chi tiết nhật ký
                                            </x-filament::link>
                                        </div>
                                    @endif
                                    
                                    @if($log->amount_diff != 0)
                                        <div class="mt-2 flex justify-end">
                                            <span class="px-4 py-1.5 rounded-lg font-mono font-black text-sm shadow-sm
                                                {{ $log->amount_diff > 0 ? 'bg-emerald-500/10 text-emerald-600 border border-emerald-500/20' : 'bg-rose-500/10 text-rose-600 border border-rose-500/20' }}">
                                                {{ $log->amount_diff > 0 ? '+' : '' }}{{ number_format($log->amount_diff) }} đ
                                            </span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-20 bg-white/20 rounded-3xl border-2 border-dashed border-gray-300 dark:border-gray-700 text-gray-400">
                        <x-filament::icon icon="heroicon-o-magnifying-glass" class="w-16 h-16 mx-auto mb-4 opacity-20" />
                        <p class="text-xl font-light">Không tìm thấy nhật ký nào phù hợp với bộ lọc</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- Pagination --}}
        <div class="mt-8">
             {{ $this->getLogs()->links() }}
        </div>
    </div>

</x-filament-panels::page>
