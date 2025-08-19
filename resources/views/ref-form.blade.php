@php($success = session('success'))
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký xét tuyển - Liên thông Đại học</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-lg">
        <h1 class="text-2xl font-bold mb-4 text-center">Đăng ký xét tuyển</h1>
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
        <form method="POST" action="">
            @csrf
            <div class="mb-3">
                <label class="block font-medium mb-1">Họ và tên *</label>
                <input type="text" name="full_name" value="{{ old('full_name') }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Số điện thoại *</label>
                <input type="text" name="phone" value="{{ old('phone') }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Email</label>
                <input type="email" name="email" value="{{ old('email') }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Trường đang học</label>
                <input type="text" name="current_college" value="{{ old('current_college') }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Ngày tháng năm sinh *</label>
                <input type="date" name="dob" value="{{ old('dob') }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Địa chỉ *</label>
                <input type="text" name="address" value="{{ old('address') }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Trường muốn học *</label>
                <select name="target_university" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring">
                    <option value="">-- Chọn trường --</option>
                    <option value="Đại học Giao thông Vận tải" {{ old('target_university') == 'Đại học Giao thông Vận tải' ? 'selected' : '' }}>Đại học Giao thông Vận tải</option>
                    <option value="Đại học Mở" {{ old('target_university') == 'Đại học Mở' ? 'selected' : '' }}>Đại học Mở</option>
                    <option value="Đại học Bách Khoa" {{ old('target_university') == 'Đại học Bách Khoa' ? 'selected' : '' }}>Đại học Bách Khoa</option>
                    <option value="Đại học Kinh tế Quốc dân" {{ old('target_university') == 'Đại học Kinh tế Quốc dân' ? 'selected' : '' }}>Đại học Kinh tế Quốc dân</option>
                    <option value="Đại học Ngoại thương" {{ old('target_university') == 'Đại học Ngoại thương' ? 'selected' : '' }}>Đại học Ngoại thương</option>
                    <option value="Khác" {{ old('target_university') == 'Khác' ? 'selected' : '' }}>Khác</option>
                </select>
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Ngành học</label>
                <input type="text" name="major" value="{{ old('major') }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Ghi chú</label>
                <textarea name="notes" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring">{{ old('notes') }}</textarea>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded font-semibold hover:bg-blue-700 transition">Gửi đăng ký</button>
        </form>
        <p class="mt-4 text-center text-gray-500 text-xs">&copy; {{ date('Y') }} Liên thông Đại học</p>
    </div>
</body>

</html>