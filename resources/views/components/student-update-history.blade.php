@php
    $entries = $entries ?? [];
    $currentPage = $currentPage ?? 1;
    $totalPages = $totalPages ?? 1;
    $total = $total ?? 0;
    $recordId = $recordId ?? null;
@endphp

<div class="flex flex-col gap-4">
    {{-- Thanh Phân trang gọn nhẹ --}}
    @if ($total > 0)
        <div class="flex justify-between items-center px-4 py-2.5 bg-gray-50 dark:bg-gray-800/30 rounded-xl border border-gray-150 dark:border-gray-800/60 text-xs text-gray-500 dark:text-gray-400">
            <span>Hiển thị {{ ($currentPage - 1) * 10 + 1 }}-{{ min($currentPage * 10, $total) }} trong tổng số <strong>{{ $total }}</strong> bản ghi</span>
            @if ($totalPages > 1)
                <div class="flex gap-1.5 items-center">
                    @if ($currentPage > 1)
                        <button type="button"
                            data-page="{{ $currentPage - 1 }}"
                            class="history-page-btn px-2.5 py-1 bg-gray-200/50 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:opacity-90 transition text-[11px] font-medium cursor-pointer no-underline">‹ Trước</button>
                    @endif
                    <span class="px-2 font-medium text-gray-700 dark:text-gray-300">Trang {{ $currentPage }}/{{ $totalPages }}</span>
                    @if ($currentPage < $totalPages)
                        <button type="button"
                            data-page="{{ $currentPage + 1 }}"
                            class="history-page-btn px-2.5 py-1 bg-gray-200/50 hover:bg-gray-200 dark:bg-gray-800 dark:hover:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg hover:opacity-90 transition text-[11px] font-medium cursor-pointer no-underline">Sau ›</button>
                    @endif
                </div>
            @endif
        </div>
    @endif

    {{-- Danh sách các log --}}
    @forelse ($entries as $entry)
        <div class="border border-gray-200 dark:border-gray-800/60 rounded-xl bg-white dark:bg-gray-900/50 p-4 shadow-sm transition hover:shadow-md">
            {{-- Dòng header: Thời gian + Tên người dùng thực hiện --}}
            <div class="flex justify-between items-center text-xs text-gray-400 dark:text-gray-500 mb-3 pb-2 border-b border-gray-100 dark:border-gray-800/30">
                <div class="flex items-center gap-1.5">
                    <span class="font-medium text-gray-600 dark:text-gray-400">{{ $entry['time'] ?? '' }}</span>
                </div>
                <span class="px-2.5 py-0.5 rounded-full text-[11px] font-semibold bg-gray-100 dark:bg-gray-800 text-gray-700 dark:text-gray-300">
                    👤 {{ $entry['user'] ?? 'Hệ thống' }}
                </span>
            </div>

            {{-- Danh sách các thay đổi --}}
            <div class="flex flex-col gap-2">
                @foreach ($entry['changes'] ?? [] as $change)
                    @php
                        $from = ($change['from'] ?? '') !== '' ? $change['from'] : 'Trống';
                        $to = ($change['to'] ?? '') !== '' ? $change['to'] : 'Trống';
                    @endphp
                    <div class="flex flex-wrap gap-1.5 items-center leading-relaxed text-[13px] text-gray-700 dark:text-gray-300">
                        <span class="font-medium text-gray-900 dark:text-gray-100 bg-gray-50 dark:bg-gray-800/50 px-2 py-0.5 rounded-md">{{ $change['label'] ?? '' }}</span>
                        <span class="text-gray-400 dark:text-gray-500 italic">{{ $from }}</span>
                        <span class="text-gray-400 dark:text-gray-600 mx-0.5">→</span>
                        <span class="font-semibold text-primary-600 dark:text-primary-400">{{ $to }}</span>
                    </div>
                @endforeach
            </div>

            {{-- Lý do chỉnh sửa (nếu có) --}}
            @if(!empty($entry['reason']))
                <div class="mt-3 p-3 bg-primary-500/5 dark:bg-primary-500/10 border-l-4 border-primary-500/60 rounded-r-lg">
                    <span class="text-[11px] font-bold text-primary-600 dark:text-primary-400 uppercase tracking-wider">Lý do thay đổi</span>
                    <span class="text-xs text-gray-600 dark:text-gray-300 italic block mt-1">{{ $entry['reason'] }}</span>
                </div>
            @endif
        </div>
    @empty
        <div class="border border-dashed border-gray-300 dark:border-gray-700 rounded-xl bg-gray-50/50 dark:bg-gray-900/30 p-8 text-center text-xs text-gray-400 dark:text-gray-500">
            📭 Chưa có lịch sử chỉnh sửa thông tin học viên.
        </div>
    @endforelse
</div>

<script>
    document.querySelectorAll('.history-page-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const page = this.getAttribute('data-page');
            window.location.href = updateHistoryPage(page);
        });
    });
</script>
