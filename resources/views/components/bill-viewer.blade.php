<div class="p-6 bg-gradient-to-br from-blue-50 to-indigo-100 rounded-lg">
    <!-- Lý do chỉnh sửa (nếu có) -->
    @if($payment->edit_reason)
    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-xl p-6 mb-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div class="w-6 h-6 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-3 h-3 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-3 flex-1">
                <h4 class="text-base font-semibold text-yellow-800 mb-2">Lý do chỉnh sửa</h4>
                <div class="bg-white rounded-lg p-4 border border-yellow-200">
                    <p class="text-sm text-yellow-700 leading-relaxed">{{ $payment->edit_reason }}</p>
                </div>
                <p class="mt-3 text-xs text-yellow-600 flex items-center">
                    <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Chỉnh sửa lúc: {{ optional($payment->edited_at)->format('d/m/Y H:i:s') }}
                </p>
            </div>
        </div>
    </div>
    @endif

    <div class="bg-white p-4 rounded-lg shadow-sm">
        <div class="flex items-center mb-3">
            <svg class="w-5 h-5 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <span class="font-semibold text-gray-700">Bill Thanh Toán</span>
        </div>

        @php
        $fileUrl = route('files.bill.view', $payment->id);
        $fileExtension = pathinfo($payment->bill_path ?? '', PATHINFO_EXTENSION);
        $isImage = in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
        @endphp

        @if(!empty($payment->bill_path))
        @if($isImage)
        <div class="mb-4">
            <img src="{{ $fileUrl }}" alt="Bill thanh toán" class="max-w-full h-auto rounded-lg shadow-md border">
        </div>
        @else
        <div class="mb-4 p-4 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
            <div class="flex items-center justify-center">
                <svg class="w-12 h-12 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="text-gray-500">File {{ strtoupper($fileExtension ?: 'PDF') }}</span>
            </div>
        </div>
        @endif

        <div class="flex space-x-3">
            <a href="{{ $fileUrl }}" target="_blank" class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                </svg>
                Xem đầy đủ
            </a>

            <a href="{{ $fileUrl }}" download class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                Tải xuống
            </a>
        </div>
        @else
        <div class="text-center py-8 text-gray-500">
            <svg class="w-12 h-12 mx-auto mb-3 text-gray-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
            </svg>
            <p>Chưa có bill thanh toán</p>
        </div>
        @endif
    </div>
</div>