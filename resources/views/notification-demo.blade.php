<!DOCTYPE html>
<html lang="vi" class="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Notification Demo</title>
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/heroicons@2.0.18/24/outline/index.css">
</head>
<body class="bg-gray-900 text-white min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Notification Bell Demo</h1>
        
        <div class="flex justify-between items-center mb-8">
            <div>
                <h2 class="text-xl font-semibold mb-2">Thông báo In-App</h2>
                <p class="text-gray-400">Click vào icon chuông để xem thông báo</p>
            </div>
            
            <!-- Notification Bell Component -->
            <x-notification-bell />
        </div>

        <div class="bg-gray-800 rounded-lg p-6 mb-8">
            <h3 class="text-lg font-semibold mb-4">Tính năng</h3>
            <ul class="space-y-2 text-gray-300">
                <li>✅ Hiển thị số lượng thông báo chưa đọc</li>
                <li>✅ Dropdown menu với danh sách thông báo</li>
                <li>✅ Đánh dấu thông báo đã đọc</li>
                <li>✅ Đánh dấu tất cả đã đọc</li>
                <li>✅ Animation và hiệu ứng</li>
                <li>✅ Responsive design</li>
                <li>✅ Dark mode support</li>
            </ul>
        </div>

        <div class="bg-gray-800 rounded-lg p-6">
            <h3 class="text-lg font-semibold mb-4">Hướng dẫn sử dụng</h3>
            <ol class="space-y-2 text-gray-300 list-decimal list-inside">
                <li>Click vào icon chuông ở góc trên bên phải</li>
                <li>Xem danh sách thông báo trong dropdown</li>
                <li>Click "Đánh dấu đã đọc" để đánh dấu từng thông báo</li>
                <li>Click "Đánh dấu tất cả đã đọc" để đánh dấu tất cả</li>
                <li>Click "Xem tất cả thông báo" để vào trang quản lý</li>
            </ol>
        </div>

        <div class="mt-8 text-center">
            <a href="/admin" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2 px-4 rounded-lg transition-colors duration-200">
                Vào Admin Panel
            </a>
        </div>
    </div>

    <style>
        /* Custom styles for demo */
        .container {
            max-width: 1200px;
        }
    </style>
</body>
</html>
