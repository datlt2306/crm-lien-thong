@php
    $entries = $entries ?? [];
    $currentPage = $currentPage ?? 1;
    $totalPages = $totalPages ?? 1;
    $total = $total ?? 0;
    $recordId = $recordId ?? null;
@endphp

<div class="flex flex-col gap-3">
    {{-- Pagination info --}}
    @if ($total > 0)
        <div class="flex justify-between items-center px-3 py-2 bg-gray-800/30 rounded-md text-xs text-gray-400/80">
            <span>Hiển thị {{ ($currentPage - 1) * 10 + 1 }}-{{ min($currentPage * 10, $total) }} trong tổng
                {{ $total }} bản ghi</span>
            @if ($totalPages > 1)
                <div class="flex gap-1 items-center">
                    @if ($currentPage > 1)
                        <button type="button"
                            data-page="{{ $currentPage - 1 }}"
                            class="history-page-btn px-2 py-1 bg-blue-500/20 text-blue-300 rounded hover:opacity-80 transition-opacity text-[11px] cursor-pointer no-underline">‹ Trước</button>
                    @endif
                    <span class="px-2 py-1 text-gray-200/90">Trang
                        {{ $currentPage }}/{{ $totalPages }}</span>
                    @if ($currentPage < $totalPages)
                        <button type="button"
                            data-page="{{ $currentPage + 1 }}"
                            class="history-page-btn px-2 py-1 bg-blue-500/20 text-blue-300 rounded hover:opacity-80 transition-opacity text-[11px] cursor-pointer no-underline">Sau ›</button>
                    @endif
                </div>
            @endif
        </div>
    @endif

    @forelse ($entries as $entry)
        <div class="border border-gray-400/30 rounded-lg bg-gray-800/50 p-3 shadow-sm">
            {{-- Dòng 1: thời gian + user --}}
            <div class="flex justify-between items-center text-xs text-gray-400/80 mb-2">
                <span>{{ $entry['time'] ?? '' }}</span>
                <span class="font-medium text-gray-200/90">{{ $entry['user'] ?? 'Hệ thống' }}</span>
            </div>

            {{-- Dòng 2+: từng thay đổi, mỗi thay đổi 1 dòng gọn --}}
            <div class="flex flex-col gap-1.5">
                @foreach ($entry['changes'] ?? [] as $change)
                    @php
                        $from = ($change['from'] ?? '') !== '' ? $change['from'] : 'Trống';
                        $to = ($change['to'] ?? '') !== '' ? $change['to'] : 'Trống';
                    @endphp
                    <div class="flex flex-wrap gap-1 items-center leading-normal text-[13px]">
                        <span class="font-semibold text-gray-100">{{ $change['label'] ?? '' }}:</span>
                        <span class="text-gray-300/90 opacity-80">{{ $from }}</span>
                        <span class="text-gray-400/70 mx-0.5">→</span>
                        <span class="font-medium text-blue-300">{{ $to }}</span>
                    </div>
                @endforeach
            </div>

            @if(!empty($entry['reason']))
                <div class="mt-2 px-2.5 py-1.5 bg-blue-500/10 border-l-4 border-blue-500/50 rounded-sm">
                    <span class="text-xs text-blue-300/90 font-medium">Lý do:</span>
                    <span class="text-xs text-gray-200/85 italic block mt-0.5">{{ $entry['reason'] }}</span>
                </div>
            @endif
        </div>
    @empty
        <div class="border border-gray-400/30 rounded-lg bg-gray-800/50 p-4 text-center text-xs text-gray-400/70">
            Chưa có lịch sử chỉnh sửa
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
