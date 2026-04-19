<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thông báo nộp lệ phí - {{ config('app.name') }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; }
        .glass { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(10px); }
    </style>
</head>
<body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
    <div class="max-w-md w-full glass p-8 rounded-2xl shadow-xl text-center border border-white">
        <div class="mb-6 inline-flex items-center justify-center w-20 h-20 bg-blue-100 text-blue-600 rounded-full">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-10 w-10" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
        
        <h1 class="text-2xl font-bold text-slate-900 mb-4">Thông báo nộp lệ phí</h1>
        
        <p class="text-slate-600 mb-8 leading-relaxed">
            Để đảm bảo thông tin hồ sơ được chính xác, quy trình nộp lệ phí sẽ được hướng dẫn trực tiếp bởi Cộng tác viên phụ trách của bạn.
        </p>

        <div class="bg-slate-100 p-6 rounded-xl mb-8">
            <p class="text-sm text-slate-500 mb-1 uppercase tracking-wider font-semibold">Cộng tác viên hỗ trợ</p>
            <p class="text-xl font-bold text-blue-700 mb-2">{{ $collaborator->full_name }}</p>
            @if($collaborator->phone)
                <a href="tel:{{ $collaborator->phone }}" class="inline-flex items-center text-blue-600 font-medium hover:underline">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-2" viewBox="0 0 20 20" fill="currentColor">
                        <path d="M2 3a1 1 0 011-1h2.153a1 1 0 01.986.836l.74 4.435a1 1 0 01-.54 1.06l-1.548.773a11.037 11.037 0 004.814 4.814l.773-1.548a1 1 0 011.06-.54l4.435.74a1 1 0 01.836.986V17a1 1 0 01-1 1h-2C7.82 18 2 12.18 2 5V3z" />
                    </svg>
                    {{ $collaborator->phone }}
                </a>
            @endif
        </div>

        <p class="text-sm text-slate-400">
            Cảm ơn bạn đã quan tâm đến chương trình đào tạo của chúng tôi.
        </p>
    </div>
</body>
</html>
