<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký Cộng tác viên - CRM Liên Thông</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- Header -->
            <div class="text-center">
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900">
                    <i class="fas fa-user-plus text-blue-600"></i>
                    Đăng ký Cộng tác viên
                </h2>
                <p class="mt-2 text-sm text-gray-600">
                    Điền thông tin để đăng ký trở thành cộng tác viên
                </p>
            </div>

            <!-- Success/Error Messages -->
            @if(session('success'))
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('success') }}</span>
            </div>
            @endif

            @if(session('error'))
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                <span class="block sm:inline">{{ session('error') }}</span>
            </div>
            @endif

            <!-- Registration Form -->
            <form class="mt-8 space-y-6" method="POST" action="{{ route('collaborator.register.submit') }}">
                @csrf
                <div class="bg-white shadow-md rounded-lg p-6 space-y-6">
                    <!-- Full Name -->
                    <div>
                        <label for="full_name" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-user text-gray-400"></i>
                            Họ và tên *
                        </label>
                        <input id="full_name"
                            name="full_name"
                            type="text"
                            required
                            value="{{ old('full_name') }}"
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm @error('full_name') border-red-500 @enderror"
                            placeholder="Nhập họ và tên đầy đủ">
                        @error('full_name')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Phone -->
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-phone text-gray-400"></i>
                            Số điện thoại *
                        </label>
                        <input id="phone"
                            name="phone"
                            type="tel"
                            required
                            value="{{ old('phone') }}"
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm @error('phone') border-red-500 @enderror"
                            placeholder="Nhập số điện thoại">
                        @error('phone')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-envelope text-gray-400"></i>
                            Email
                        </label>
                        <input id="email"
                            name="email"
                            type="email"
                            value="{{ old('email') }}"
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm @error('email') border-red-500 @enderror"
                            placeholder="Nhập email (tùy chọn)">
                        @error('email')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Organization -->
                    <div>
                        <label for="organization_id" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-building text-gray-400"></i>
                            Tổ chức *
                        </label>
                        <select id="organization_id"
                            name="organization_id"
                            required
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('organization_id') border-red-500 @enderror">
                            <option value="">-- Chọn tổ chức --</option>
                            @foreach($organizations as $org)
                            <option value="{{ $org->id }}" {{ old('organization_id') == $org->id ? 'selected' : '' }}>
                                {{ $org->name }}
                            </option>
                            @endforeach
                        </select>
                        @error('organization_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Upline -->
                    <div>
                        <label for="upline_id" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-users text-gray-400"></i>
                            Cộng tác viên giới thiệu
                        </label>
                        <select id="upline_id"
                            name="upline_id"
                            class="mt-1 block w-full px-3 py-2 border border-gray-300 bg-white rounded-md shadow-sm focus:outline-none focus:ring-blue-500 focus:border-blue-500 sm:text-sm @error('upline_id') border-red-500 @enderror">
                            <option value="">-- Không có (CTV cấp 1) --</option>
                            @foreach($collaborators as $collaborator)
                            <option value="{{ $collaborator->id }}" {{ old('upline_id') == $collaborator->id ? 'selected' : '' }}>
                                {{ $collaborator->full_name }} ({{ $collaborator->ref_id }})
                            </option>
                            @endforeach
                        </select>
                        @error('upline_id')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <p class="mt-1 text-xs text-gray-500">
                            Nếu bạn được giới thiệu bởi một cộng tác viên khác, hãy chọn họ ở đây
                        </p>
                    </div>

                    <!-- Note -->
                    <div>
                        <label for="note" class="block text-sm font-medium text-gray-700">
                            <i class="fas fa-sticky-note text-gray-400"></i>
                            Ghi chú
                        </label>
                        <textarea id="note"
                            name="note"
                            rows="3"
                            class="mt-1 appearance-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm @error('note') border-red-500 @enderror"
                            placeholder="Nhập ghi chú thêm (tùy chọn)">{{ old('note') }}</textarea>
                        @error('note')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button type="submit"
                            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition duration-150 ease-in-out">
                            <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                                <i class="fas fa-paper-plane text-blue-500 group-hover:text-blue-400"></i>
                            </span>
                            Đăng ký Cộng tác viên
                        </button>
                    </div>
                </div>
            </form>

            <!-- Status Check Section -->
            <div class="bg-white shadow-md rounded-lg p-6">
                <h3 class="text-lg font-medium text-gray-900 mb-4">
                    <i class="fas fa-search text-green-600"></i>
                    Kiểm tra trạng thái đăng ký
                </h3>
                <div x-data="statusChecker()" class="space-y-4">
                    <div class="flex space-x-2">
                        <input x-model="phone"
                            type="tel"
                            placeholder="Nhập số điện thoại đã đăng ký"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500">
                        <button @click="checkStatus()"
                            :disabled="loading"
                            class="px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50">
                            <i class="fas fa-search" x-show="!loading"></i>
                            <i class="fas fa-spinner fa-spin" x-show="loading"></i>
                            <span x-text="loading ? 'Đang kiểm tra...' : 'Kiểm tra'"></span>
                        </button>
                    </div>

                    <div x-show="result" class="mt-4 p-4 rounded-md"
                        :class="{
                             'bg-green-100 text-green-700 border border-green-400': result?.status === 'approved',
                             'bg-yellow-100 text-yellow-700 border border-yellow-400': result?.status === 'pending',
                             'bg-red-100 text-red-700 border border-red-400': result?.status === 'rejected',
                             'bg-gray-100 text-gray-700 border border-gray-400': result?.status === 'error'
                         }">
                        <div x-show="result?.status === 'approved'" class="flex items-center">
                            <i class="fas fa-check-circle mr-2"></i>
                            <span x-text="result.message"></span>
                        </div>
                        <div x-show="result?.status === 'pending'" class="flex items-center">
                            <i class="fas fa-clock mr-2"></i>
                            <span x-text="result.message"></span>
                        </div>
                        <div x-show="result?.status === 'rejected'" class="flex items-center">
                            <i class="fas fa-times-circle mr-2"></i>
                            <div>
                                <div x-text="result.message"></div>
                                <div x-show="result.rejection_reason" class="mt-2 text-sm">
                                    <strong>Lý do:</strong> <span x-text="result.rejection_reason"></span>
                                </div>
                            </div>
                        </div>
                        <div x-show="result?.status === 'error'" class="flex items-center">
                            <i class="fas fa-exclamation-circle mr-2"></i>
                            <span x-text="result.error"></span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Section -->
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                <div class="flex">
                    <div class="flex-shrink-0">
                        <i class="fas fa-info-circle text-blue-400"></i>
                    </div>
                    <div class="ml-3">
                        <h3 class="text-sm font-medium text-blue-800">
                            Thông tin quan trọng
                        </h3>
                        <div class="mt-2 text-sm text-blue-700">
                            <ul class="list-disc list-inside space-y-1">
                                <li>Đăng ký của bạn sẽ được xem xét bởi quản trị viên</li>
                                <li>Bạn sẽ nhận được thông báo qua điện thoại hoặc email</li>
                                <li>Sau khi được duyệt, bạn sẽ trở thành cộng tác viên chính thức</li>
                                <li>Bạn có thể kiểm tra trạng thái đăng ký bằng số điện thoại</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function statusChecker() {
            return {
                phone: '',
                loading: false,
                result: null,

                async checkStatus() {
                    if (!this.phone) {
                        alert('Vui lòng nhập số điện thoại');
                        return;
                    }

                    this.loading = true;
                    this.result = null;

                    try {
                        const response = await fetch('{{ route("collaborator.check.status") }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                phone: this.phone
                            })
                        });

                        const data = await response.json();

                        if (response.ok) {
                            this.result = data;
                        } else {
                            this.result = {
                                status: 'error',
                                error: data.error || 'Có lỗi xảy ra'
                            };
                        }
                    } catch (error) {
                        this.result = {
                            status: 'error',
                            error: 'Không thể kết nối đến server'
                        };
                    } finally {
                        this.loading = false;
                    }
                }
            }
        }
    </script>
</body>

</html>