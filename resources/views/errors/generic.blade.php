<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lỗi - Liên thông Đại học</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css">
</head>
<body class="bg-gray-100 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded shadow-md w-full max-w-lg text-center">
        <div class="text-6xl mb-4 text-red-500">⚠️</div>
        <h1 class="text-2xl font-bold mb-4 text-gray-800">Có lỗi xảy ra</h1>
        <p class="text-gray-600 mb-6">{{ $message ?? 'Đã có lỗi xảy ra. Vui lòng thử lại sau.' }}</p>
        <div class="space-y-3">
            <a href="/" class="inline-block bg-blue-600 text-white py-2 px-4 rounded hover:bg-blue-700 transition">
                Về trang chủ
            </a>
            <button onclick="history.back()" class="inline-block bg-gray-600 text-white py-2 px-4 rounded hover:bg-gray-700 transition ml-2">
                Quay lại
            </button>
        </div>
    </div>
</body>
</html>
