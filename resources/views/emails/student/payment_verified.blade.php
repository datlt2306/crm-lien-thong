<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Xác nhận thanh toán thành công</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .header { background-color: #28a745; color: #fff; padding: 15px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
        .info-box { background-color: #f8f9fa; border-left: 4px solid #28a745; padding: 15px; margin: 15px 0; }
        .receipt-link { display: inline-block; padding: 10px 20px; margin-top: 15px; background-color: #28a745; color: white; text-decoration: none; border-radius: 5px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Xác Nhận Thanh Toán Thành Công</h2>
        </div>
        <div class="content">
            <p>Kính gửi <strong>{{ $student->full_name }}</strong>,</p>
            <p>Ban tuyển sinh xin xác nhận đã nhận được khoản lệ phí đăng ký của bạn cho hồ sơ mã số: <strong>{{ $student->profile_code }}</strong>.</p>
            
            <div class="info-box">
                <p><strong>Thông tin thanh toán:</strong></p>
                <ul>
                    <li>Số tiền: <strong>{{ number_format($payment->amount, 0, ',', '.') }} VND</strong></li>
                    <li>Mã số phiếu thu: <strong>{{ $payment->receipt_number }}</strong></li>
                    <li>Ngày xác nhận: <strong>{{ $payment->verified_at->format('d/m/Y H:i') }}</strong></li>
                    <li>Chương trình: <strong>{{ $student->major }} - {{ $payment->program_type === 'REGULAR' ? 'Chính quy' : 'Vừa học vừa làm' }}</strong></li>
                </ul>
            </div>

            <p>Bạn có thể xem và tải phiếu thu chính thức bằng cách nhấn vào nút bên dưới:</p>
            
            <div style="text-align: center;">
                <a href="{{ url('/track-profile?profile_code=' . $student->profile_code) }}" class="receipt-link" style="color: #fff;">Xem phiếu thu & Hồ sơ</a>
            </div>

            <p style="margin-top: 20px;">Hồ sơ của bạn hiện đang được chuyển qua bộ phận chuyên môn để tiến hành các bước tiếp theo. Chúng tôi sẽ thông báo cho bạn ngay khi có cập nhật mới.</p>
            
            <p>Trân trọng,<br>Ban Tuyển Sinh & Tài Chính</p>
        </div>
        <div class="footer">
            <p>Đây là email tự động. Vui lòng không trả lời email này.</p>
        </div>
    </div>
</body>
</html>
