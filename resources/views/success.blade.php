<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đăng ký thành công - Liên thông Đại học</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
</head>

<body class="bg-gray-50 min-h-screen">
    <div class="container mx-auto px-4 py-8">
        <div class="max-w-md mx-auto bg-white rounded-lg shadow-lg overflow-hidden">
            <!-- Header với icon thành công -->
            <div class="bg-green-500 text-white p-6 text-center">
                <div class="text-6xl mb-4">
                    <i class="fas fa-check-circle"></i>
                </div>
                <h1 class="text-2xl font-bold">
                    @if($type === 'payment')
                    Tải lên hóa đơn thành công!
                    @else
                    Đăng ký thành công!
                    @endif
                </h1>
            </div>

            <!-- Nội dung thông báo -->
            <div class="p-6">
                <div class="text-center mb-6">
                    @if($type === 'payment')
                    <p class="text-gray-600 mb-4">
                        Cảm ơn bạn đã tải lên hóa đơn thanh toán!
                    </p>
                    <p class="text-gray-600 mb-6">
                        Chúng tôi sẽ xác nhận hóa đơn và liên hệ với bạn trong thời gian sớm nhất.
                    </p>
                    @else
                    <p class="text-gray-600 mb-4">
                        Cảm ơn bạn đã đăng ký tham gia chương trình liên thông đại học!
                    </p>
                    <p class="text-gray-600 mb-6">
                        Chúng tôi sẽ liên hệ với bạn trong thời gian sớm nhất để hướng dẫn các bước tiếp theo.
                    </p>
                    @endif
                </div>

                <!-- Thông tin liên hệ -->
                <div class="bg-blue-50 rounded-lg p-4 mb-6">
                    <h3 class="font-semibold text-blue-800 mb-3">
                        <i class="fas fa-info-circle mr-2"></i>
                        Thông tin liên hệ
                    </h3>
                    <div class="space-y-2 text-sm text-blue-700">
                        <p><i class="fas fa-phone mr-2"></i> Hotline: 1900 xxxx</p>
                        <p><i class="fas fa-envelope mr-2"></i> Email: info@lienthong.edu.vn</p>
                        <p><i class="fas fa-clock mr-2"></i> Giờ làm việc: 8:00 - 17:00 (Thứ 2 - Thứ 6)</p>
                    </div>
                </div>

                <!-- Các bước tiếp theo -->
                <div class="bg-yellow-50 rounded-lg p-4 mb-6">
                    <h3 class="font-semibold text-yellow-800 mb-3">
                        <i class="fas fa-list-check mr-2"></i>
                        Các bước tiếp theo
                    </h3>
                    <div class="space-y-2 text-sm text-yellow-700">
                        @if($type === 'payment')
                        <p><i class="fas fa-1 mr-2"></i> Chờ xác nhận hóa đơn từ chủ đơn vị</p>
                        <p><i class="fas fa-2 mr-2"></i> Nhận thông báo xác nhận qua điện thoại</p>
                        <p><i class="fas fa-3 mr-2"></i> Hoàn tất thủ tục nhập học</p>
                        <p><i class="fas fa-4 mr-2"></i> Bắt đầu quá trình học tập</p>
                        @else
                        <p><i class="fas fa-1 mr-2"></i> Nhận cuộc gọi tư vấn từ chuyên viên</p>
                        <p><i class="fas fa-2 mr-2"></i> Chuẩn bị hồ sơ cần thiết</p>
                        <p><i class="fas fa-3 mr-2"></i> Nộp hồ sơ và thanh toán</p>
                        <p><i class="fas fa-4 mr-2"></i> Nhận thông báo kết quả</p>
                        @endif
                    </div>
                </div>

                <!-- Nút hành động -->
                <div class="space-y-3">
                    <a href="/" class="w-full bg-blue-600 text-white py-3 px-4 rounded-lg hover:bg-blue-700 transition duration-200 flex items-center justify-center">
                        <i class="fas fa-home mr-2"></i>
                        Về trang chủ
                    </a>

                    @if(isset($payment_success) && $payment_success && !empty($ref_id))
                    <a href="{{ route('public.ref.payment.form', ['ref_id' => e($ref_id)]) }}" class="w-full bg-green-600 text-white py-3 px-4 rounded-lg hover:bg-green-700 transition duration-200 flex items-center justify-center">
                        <i class="fas fa-upload mr-2"></i>
                        Upload thêm bill
                    </a>
                    @endif
                </div>

                <!-- Lưu ý -->
                <div class="mt-6 text-center">
                    <p class="text-xs text-gray-500">
                        <i class="fas fa-shield-alt mr-1"></i>
                        Thông tin của bạn được bảo mật tuyệt đối
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Script để tự động chuyển về trang chủ sau 10 giây -->
    <script>
        setTimeout(function() {
            window.location.href = '/';
        }, 10000); // 10 giây
    </script>
</body>

</html>