@php
    $entries = $entries ?? [];
    $currentPage = $currentPage ?? 1;
    $totalPages = $totalPages ?? 1;
    $total = $total ?? 0;
    $recordId = $recordId ?? null;
@endphp

<div style="display: flex; flex-direction: column; gap: 12px;">
    {{-- Pagination info --}}
    @if ($total > 0)
        <div
            style="display: flex; justify-content: space-between; align-items: center; padding: 8px 12px; background: rgba(31, 41, 55, 0.3); border-radius: 6px; font-size: 12px; color: rgba(209, 213, 219, 0.8);">
            <span>Hiển thị {{ ($currentPage - 1) * 10 + 1 }}-{{ min($currentPage * 10, $total) }} trong tổng
                {{ $total }} bản ghi</span>
            @if ($totalPages > 1)
                <div style="display: flex; gap: 4px; align-items: center;">
                    @if ($currentPage > 1)
                        <a href="javascript:void(0)"
                            onclick="window.location.href = updateHistoryPage({{ $currentPage - 1 }})"
                            style="padding: 4px 8px; background: rgba(59, 130, 246, 0.2); color: rgba(147, 197, 253, 1); border-radius: 4px; text-decoration: none; font-size: 11px; cursor: pointer; transition: opacity 0.2s;"
                            onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">‹ Trước</a>
                    @endif
                    <span style="padding: 4px 8px; color: rgba(243, 244, 246, 0.9);">Trang
                        {{ $currentPage }}/{{ $totalPages }}</span>
                    @if ($currentPage < $totalPages)
                        <a href="javascript:void(0)"
                            onclick="window.location.href = updateHistoryPage({{ $currentPage + 1 }})"
                            style="padding: 4px 8px; background: rgba(59, 130, 246, 0.2); color: rgba(147, 197, 253, 1); border-radius: 4px; text-decoration: none; font-size: 11px; cursor: pointer; transition: opacity 0.2s;"
                            onmouseover="this.style.opacity='0.8'" onmouseout="this.style.opacity='1'">Sau ›</a>
                    @endif
                </div>
            @endif
        </div>
    @endif

    @forelse ($entries as $entry)
        <div
            style="border: 1px solid rgba(156, 163, 175, 0.3); border-radius: 8px; background: rgba(31, 41, 55, 0.5); padding: 10px 12px; box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.2);">
            {{-- Dòng 1: thời gian + user --}}
            <div
                style="display: flex; justify-content: space-between; align-items: center; font-size: 12px; color: rgba(209, 213, 219, 0.8); margin-bottom: 8px;">
                <span>{{ $entry['time'] ?? '' }}</span>
                <span
                    style="font-weight: 500; color: rgba(243, 244, 246, 0.9);">{{ $entry['user'] ?? 'Hệ thống' }}</span>
            </div>

            {{-- Dòng 2+: từng thay đổi, mỗi thay đổi 1 dòng gọn --}}
            <div style="display: flex; flex-direction: column; gap: 6px;">
                @foreach ($entry['changes'] ?? [] as $change)
                    @php
                        $from = ($change['from'] ?? '') !== '' ? $change['from'] : 'Trống';
                        $to = ($change['to'] ?? '') !== '' ? $change['to'] : 'Trống';
                    @endphp
                    <div
                        style="display: flex; flex-wrap: wrap; gap: 4px; align-items: center; line-height: 1.5; font-size: 13px;">
                        <span
                            style="font-weight: 600; color: rgba(243, 244, 246, 1);">{{ $change['label'] ?? '' }}:</span>
                        <span style="color: rgba(209, 213, 219, 0.9); opacity: 0.8;">{{ $from }}</span>
                        <span style="color: rgba(156, 163, 175, 0.7); margin: 0 2px;">→</span>
                        <span style="font-weight: 500; color: rgba(147, 197, 253, 1);">{{ $to }}</span>
                    </div>
                @endforeach
            </div>

            @if(!empty($entry['reason']))
                <div style="margin-top: 8px; padding: 6px 10px; background: rgba(59, 130, 246, 0.1); border-left: 3px solid rgba(59, 130, 246, 0.5); border-radius: 2px;">
                    <span style="font-size: 12px; color: rgba(147, 197, 253, 0.9); font-weight: 500;">Lý do:</span>
                    <span style="font-size: 12px; color: rgba(243, 244, 246, 0.85); font-style: italic; display: block; margin-top: 2px;">{{ $entry['reason'] }}</span>
                </div>
            @endif
        </div>
    @empty
        <div
            style="border: 1px solid rgba(156, 163, 175, 0.3); border-radius: 8px; background: rgba(31, 41, 55, 0.5); padding: 16px; text-align: center; font-size: 12px; color: rgba(209, 213, 219, 0.7);">
            Chưa có lịch sử chỉnh sửa
        </div>
    @endforelse
</div>
