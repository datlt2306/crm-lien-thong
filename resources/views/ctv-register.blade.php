@php($success = session('success'))
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký Cộng tác viên</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>

<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-lg">
        <h1 class="text-2xl font-bold mb-4 text-center">Đăng ký Cộng tác viên</h1>
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
                <label class="block font-medium mb-1">Email *</label>
                <input type="email" name="email" value="{{ old('email') }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Số điện thoại *</label>
                <input type="text" name="phone" value="{{ old('phone') }}" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Đơn vị</label>
                <select name="organization_id" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring">
                    <option value="">-- Chọn đơn vị --</option>
                    @foreach(($organizations ?? []) as $id => $name)
                    <option value="{{ $id }}" {{ old('organization_id') == $id ? 'selected' : '' }}>{{ $name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="mb-3 grid grid-cols-1 md:grid-cols-2 gap-3">
                <div>
                    <label class="block font-medium mb-1">Mật khẩu *</label>
                    <input type="password" name="password" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
                </div>
                <div>
                    <label class="block font-medium mb-1">Xác nhận mật khẩu *</label>
                    <input type="password" name="password_confirmation" required class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
                </div>
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Mã ref CTV cấp trên (nếu có)</label>
                <input type="text" name="upline_ref" value="{{ old('upline_ref') }}" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring" />
            </div>
            <div class="mb-3">
                <label class="block font-medium mb-1">Ghi chú</label>
                <textarea name="note" class="w-full border rounded px-3 py-2 focus:outline-none focus:ring">{{ old('note') }}</textarea>
            </div>
            <button type="submit" class="w-full bg-blue-600 text-white py-2 rounded font-semibold hover:bg-blue-700 transition">Đăng ký</button>
        </form>
    </div>
</body>

</html>