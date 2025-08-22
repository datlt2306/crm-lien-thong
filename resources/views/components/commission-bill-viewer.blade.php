<div class="p-6 bg-gradient-to-br from-blue-50 to-indigo-100 rounded-lg">
    <div class="mb-6">
        <h3 class="text-2xl font-bold text-gray-800 mb-2">Bill Thanh Toán Hoa Hồng</h3>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div class="bg-white p-4 rounded-lg shadow-sm">
                <div class="flex items-center mb-3">
                    <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                    <span class="font-semibold text-gray-700">Thông tin CTV</span>
                </div>
                <p class="text-gray-600">{{ $commissionItem->recipient->full_name }}</p>
                <p class="text-sm text-gray-500">{{ $commissionItem->recipient->email }}</p>
            </div>

            <div class="bg-white p-4 rounded-lg shadow-sm">
                <div class="flex items-center mb-3">
                    <svg class="w-5 h-5 text-green-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1"></path>
                    </svg>
                    <span class="font-semibold text-gray-700">Số tiền hoa hồng</span>
                </div>
                <p class="text-2xl font-bold text-green-600">{{ number_format($commissionItem->amount, 0, ',', '.') }} VNĐ</p>
            </div>
        </div>
    </div>

    <div class="mb-6">
        <div class="bg-white p-4 rounded-lg shadow-sm">
            <div class="flex items-center mb-3">
                <svg class="w-5 h-5 text-purple-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                </svg>
                <span class="font-semibold text-gray-700">Bill Thanh Toán</span>
            </div>

            @if($commissionItem->payment_bill_path)
            @php
            $fileExtension = pathinfo($commissionItem->payment_bill_path, PATHINFO_EXTENSION);
            $isImage = in_array(strtolower($fileExtension), ['jpg', 'jpeg', 'png', 'gif', 'webp']);
            @endphp

            @if($isImage)
            <div class="mb-4">
                <img src="{{ route('files.commission-bill.view', $commissionItem->id) }}"
                    alt="Bill thanh toán"
                    class="max-w-full h-auto rounded-lg shadow-md border">
            </div>
            @else
            <div class="mb-4 p-4 bg-gray-50 rounded-lg border-2 border-dashed border-gray-300">
                <div class="flex items-center justify-center">
                    <svg class="w-12 h-12 text-gray-400 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                    </svg>
                    <span class="text-gray-500">File PDF</span>
                </div>
            </div>
            @endif

            <div class="flex space-x-3">
                <a href="{{ route('files.commission-bill.view', $commissionItem->id) }}"
                    target="_blank"
                    class="inline-flex items-center px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                    </svg>
                    Xem đầy đủ
                </a>

                <a href="{{ route('files.commission-bill.view', $commissionItem->id) }}"
                    download
                    class="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition-colors">
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

    @if($commissionItem->payment_confirmed_at)
    <div class="bg-white p-4 rounded-lg shadow-sm">
        <div class="flex items-center mb-3">
            <svg class="w-5 h-5 text-blue-500 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path>
            </svg>
            <span class="font-semibold text-gray-700">Thông tin xác nhận</span>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <p class="text-sm text-gray-500">Xác nhận thanh toán bởi:</p>
                <p class="font-medium text-gray-700">{{ $commissionItem->paymentConfirmedBy->name ?? 'N/A' }}</p>
                <p class="text-sm text-gray-500">{{ $commissionItem->payment_confirmed_at->format('d/m/Y H:i:s') }}</p>
            </div>

            @if($commissionItem->received_confirmed_at)
            <div>
                <p class="text-sm text-gray-500">Xác nhận nhận tiền bởi:</p>
                <p class="font-medium text-gray-700">{{ $commissionItem->receivedConfirmedBy->name ?? 'N/A' }}</p>
                <p class="text-sm text-gray-500">{{ $commissionItem->received_confirmed_at->format('d/m/Y H:i:s') }}</p>
            </div>
            @endif
        </div>
    </div>
    @endif
</div>