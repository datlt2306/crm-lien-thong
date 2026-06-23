<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Không có quyền truy cập | Liên thông Đại học</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <link rel="stylesheet" href="/css/ref-form.css">
    <style>
        .error-container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 40px 20px;
        }
        .error-icon {
            margin-bottom: 24px;
            color: #ef4444;
        }
        .error-message {
            font-size: 16px;
            color: var(--text-muted);
            margin-bottom: 30px;
            max-width: 480px;
            line-height: 1.6;
        }
        .btn-group {
            display: flex;
            gap: 12px;
            width: 100%;
            max-width: 400px;
        }
        .btn-primary, .btn-secondary {
            flex: 1;
            padding: 12px 20px;
            border-radius: 12px;
            font-size: 15px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.2s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            cursor: pointer;
        }
        .btn-primary {
            background: linear-gradient(to right, #2563eb, #4f46e5);
            color: white;
            border: none;
        }
        .btn-primary:hover {
            filter: brightness(1.05);
            transform: translateY(-1px);
        }
        .btn-secondary {
            background: white;
            color: var(--text-main);
            border: 1px solid var(--border);
        }
        .btn-secondary:hover {
            background: #f8fafc;
            transform: translateY(-1px);
        }
    </style>
</head>
<body>
    <div class="wrap" style="margin-top: 5vh;">
        <div class="card">
            <div class="hero">
                <h1>403 - Giới hạn truy cập</h1>
                <p>Bạn không có quyền xem trang hoặc tài liệu này.</p>
            </div>
            <div class="content">
                <div class="error-container">
                    <div class="error-icon">
                        <i data-lucide="shield-alert" style="width: 72px; height: 72px;"></i>
                    </div>
                    <p class="error-message">
                        {{ $exception->getMessage() ?: 'Bạn không có quyền thực hiện hành động này hoặc phiên đăng nhập của bạn không đủ đặc quyền để xem dữ liệu được yêu cầu.' }}
                    </p>
                    <div class="btn-group">
                        <a href="javascript:history.back()" class="btn-secondary">
                            <i data-lucide="arrow-left" style="width: 18px; height: 18px;"></i>
                            Quay lại
                        </a>
                        <a href="/" class="btn-primary">
                            <i data-lucide="home" style="width: 18px; height: 18px;"></i>
                            Trang chủ
                        </a>
                    </div>
                </div>
                <p class="footer">&copy; {{ date('Y') }} Liên thông Đại học</p>
            </div>
        </div>
    </div>
    <script>
        if (window.lucide) {
            window.lucide.createIcons();
        }
    </script>
</body>
</html>
