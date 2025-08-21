@php($success = session('success'))
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nộp học phí - Tải hóa đơn</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-lg">
        <h1 class="text-2xl font-bold mb-4 text-center">Nộp học phí</h1>
        <p class="mb-2 text-center text-gray-600">Mã giới thiệu: <span class="font-semibold text-blue-600">{{ $ref_id }}</span></p>

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
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded font-semibold hover:bg-blue-700 transition">Gửi hóa đơn</button>
        </form>

        <p class="mt-4 text-center text-gray-500 text-xs">&copy; {{ date('Y') }} Liên thông Đại học</p>
    </div>
</body>

</html>