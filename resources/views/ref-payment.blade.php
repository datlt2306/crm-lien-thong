@php($success = session('success'))
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nộp học phí - Tải hóa đơn</title>
    @vite(['resources/css/app.css'])
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-lg">
        <h1 class="text-2xl font-bold mb-4 text-center">Nộp học phí</h1>
        <p class="mb-2 text-center text-gray-600">Mã giới thiệu: <span class="font-semibold text-blue-600">{{ e($ref_id) }}</span></p>

        @if($success)
        <div class="bg-green-100 text-green-800 p-3 rounded mb-4 text-center">{{ $success }}</div>
        @endif

        @if($errors->any())
        <div class="bg-red-100 text-red-800 p-3 rounded mb-4">
            <ul class="list-disc pl-5">
                @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
        @endif

        <form method="POST" action="" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label class="block font-medium mb-1">Số điện thoại đã đăng ký *</label>
                <input type="text" name="phone" value="{{ old('phone') }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Số tiền đã nộp (VND) *</label>
                <input type="number" name="amount" value="{{ old('amount') }}" min="1000" step="1000" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Hệ đào tạo *</label>
                <select name="program_type" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring">
                    <option value="">-- Chọn hệ --</option>
                    <option value="REGULAR" {{ old('program_type') == 'REGULAR' ? 'selected' : '' }}>Chính quy</option>
                    <option value="PART_TIME" {{ old('program_type') == 'PART_TIME' ? 'selected' : '' }}>Vừa học vừa làm</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Tải hóa đơn/bill *</label>
                <input type="file" name="bill" accept="image/*,application/pdf" required class="w-full" />
                <p class="text-xs text-gray-500 mt-1">Hỗ trợ JPG, PNG, PDF. Tối đa 5MB.</p>
            </div>
            <button type="submit" id="submit-btn" class="w-full bg-blue-600 text-white py-2 rounded font-semibold hover:bg-blue-700 transition flex items-center justify-center">
                <span id="spinner" class="hidden mr-2">
                    <svg class="animate-spin h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                </span>
                <span id="btn-text">Gửi hóa đơn</span>
            </button>
        </form>

        <p class="mt-4 text-center text-gray-500 text-xs">&copy; {{ date('Y') }} Liên thông Đại học</p>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function() {
            const btn = document.getElementById('submit-btn');
            const spinner = document.getElementById('spinner');
            const btnText = document.getElementById('btn-text');
            
            btn.disabled = true;
            btn.classList.add('opacity-50', 'cursor-not-allowed');
            spinner.classList.remove('hidden');
            btnText.textContent = 'Đang gửi...';
        });
    </script>
</body>

</html>