<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Thông tin ví tiền -->
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-6">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-semibold text-gray-900">Thông tin ví tiền</h2>
                <div class="flex items-center space-x-2">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-100 text-green-800">
                        Hoạt động
                    </span>
                </div>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Thông tin cơ bản -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tên cộng tác viên</label>
                        <p class="text-sm text-gray-900">{{ $this->data['collaborator_name'] ?? 'N/A' }}</p>
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tổ chức</label>
                        <p class="text-sm text-gray-900">{{ $this->data['organization_name'] ?? 'N/A' }}</p>
                    </div>
                </div>
                
                <!-- Thông tin tài chính -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Số dư hiện tại</label>
                        <p class="text-2xl font-bold text-green-600">
                            {{ number_format($this->data['balance'] ?? 0, 0, ',', '.') }} ₫
                        </p>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tổng đã nhận</label>
                            <p class="text-lg font-semibold text-blue-600">
                                {{ number_format($this->data['total_received'] ?? 0, 0, ',', '.') }} ₫
                            </p>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">Tổng đã chi</label>
                            <p class="text-lg font-semibold text-orange-600">
                                {{ number_format($this->data['total_paid'] ?? 0, 0, ',', '.') }} ₫
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Thống kê nhanh -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-gradient-to-r from-blue-500 to-blue-600 rounded-lg p-6 text-white">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-blue-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-blue-200">Số dư hiện tại</p>
                        <p class="text-2xl font-bold">{{ number_format($this->data['balance'] ?? 0, 0, ',', '.') }} ₫</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-green-500 to-green-600 rounded-lg p-6 text-white">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-green-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 11l5-5m0 0l5 5m-5-5v12" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-green-200">Tổng đã nhận</p>
                        <p class="text-2xl font-bold">{{ number_format($this->data['total_received'] ?? 0, 0, ',', '.') }} ₫</p>
                    </div>
                </div>
            </div>
            
            <div class="bg-gradient-to-r from-orange-500 to-orange-600 rounded-lg p-6 text-white">
                <div class="flex items-center">
                    <div class="flex-shrink-0">
                        <svg class="h-8 w-8 text-orange-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 13l-5 5m0 0l-5-5m5 5V6" />
                        </svg>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm font-medium text-orange-200">Tổng đã chi</p>
                        <p class="text-2xl font-bold">{{ number_format($this->data['total_paid'] ?? 0, 0, ',', '.') }} ₫</p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Nút hành động -->
        <div class="flex justify-center">
            <a href="{{ route('filament.admin.resources.wallet-transactions.index') }}" 
               class="inline-flex items-center px-6 py-3 border border-transparent text-base font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-colors duration-200">
                <svg class="h-5 w-5 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                Xem lịch sử giao dịch
            </a>
        </div>
    </div>
</x-filament-panels::page>
