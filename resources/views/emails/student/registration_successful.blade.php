<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Đăng ký thành công</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .header { background-color: #0046b8; color: #fff; padding: 15px; text-align: center; border-radius: 8px 8px 0 0; }
        .content { padding: 20px; }
        .footer { text-align: center; font-size: 12px; color: #777; margin-top: 20px; border-top: 1px solid #eee; padding-top: 10px; }
        .highlight { font-size: 18px; font-weight: bold; color: #0046b8; background-color: #f4f6f9; padding: 10px; text-align: center; border-radius: 5px; margin: 15px 0; }
        .button-link { display: inline-block; padding: 10px 20px; margin-top: 15px; background-color: #0046b8; color: white; text-decoration: none; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h2>Kính gửi {{ $student->full_name }},</h2>
        </div>
        <div class="content">
            <p>Chúc mừng bạn đã đăng ký form liên thông GTVT thành công!</p>
            <p>Chúng tôi đã tiếp nhận thông tin hồ sơ của bạn với các thông tin sau:</p>
            <ul style="list-style-type: none; padding-left: 0;">
                <li>- <strong>Ngành học:</strong> {{ $student->major }}</li>
                <li>- <strong>Hệ đào tạo:</strong> {{ $student->program_type_label }}</li>
                <li>- <strong>Đợt tuyển sinh:</strong> {{ $student->target_university ?? ($student->intake?->name ?? 'Chưa xác định') }}</li>
            </ul>
            <p>Dưới đây là <strong>MÃ HỒ SƠ</strong> của bạn dùng để tra cứu trạng thái và làm các thủ tục tiếp theo:</p>
            <div class="highlight">
                {{ $student->profile_code }}
            </div>
            <p>Vui lòng ghi nhớ mã này để có thể tra cứu thông tin nộp hồ sơ, thanh toán lệ phí hoặc cần hỗ trợ từ nhân viên tuyển sinh.</p>
            <div style="text-align: center;">
                <a href="{{ url('/track-profile?profile_code=' . $student->profile_code) }}" class="button-link" style="color: #fff;">Tra cứu hồ sơ của bạn</a>
            </div>
            <p style="margin-top: 20px;">Nếu bạn có bất kỳ thắc mắc nào, xin vui lòng liên hệ lại với người hướng dẫn hoặc ban tuyển sinh.</p>
            <p>Trân trọng,<br>Ban Tuyển Sinh</p>
        </div>
        <div class="footer">
            <p>Đây là email tự động. Vui lòng không trả lời email này.</p>
        </div>
    </div>
</body>
</html>
