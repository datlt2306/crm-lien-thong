    <!-- Edit Reason (if exists) -->
    @if($payment->edit_reason)
    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-xl p-6">
        <div class="flex items-start">
            <div class="flex-shrink-0">
                <div class="w-8 h-8 bg-yellow-100 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16.5c-.77.833.192 2.5 1.732 2.5z"></path>
                    </svg>
                </div>
            </div>
            <div class="ml-4 flex-1">
                <h4 class="text-lg font-semibold text-yellow-800 mb-2">Lý do chỉnh sửa</h4>
                <div class="bg-white rounded-lg p-4 border border-yellow-200">
                    <p class="text-sm text-yellow-700 leading-relaxed">{{ $payment->edit_reason }}</p>
                </div>
                <p class="mt-3 text-xs text-yellow-600 flex items-center">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                    </svg>
                    Chỉnh sửa lúc: {{ $payment->edited_at->format('d/m/Y H:i:s') }}
                </p>
            </div>
        </div>
    </div>
    @endif

    ->

    <!-- Edit Reason (if exists) -->
    @if($payment->edit_reason)
    <div class="bg-gradient-to-r from-yellow-50 to-orange-50 border border-yellow-200 rounded-xl p-6">
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
                    Chỉnh sửa lúc: {{ $payment->edited_at->format('d/m/Y H:i:s') }}
                </p>
            </div>
        </div>
    </div>
    @endif