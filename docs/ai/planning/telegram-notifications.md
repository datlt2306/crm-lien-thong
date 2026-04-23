# Kế hoạch triển khai Thông báo Telegram khi có Sinh viên đăng ký

## 1. Phân tích & Yêu cầu
- **Mục tiêu:** Tự động gửi thông báo qua Telegram khi có sinh viên mới đăng ký thành công từ hệ thống (form public hoặc admin).
- **Đối tượng nhận tin:** 
    1. Group quản trị (Admin/Staff) - Nhận tất cả thông báo.
    2. Cộng tác viên (Collaborator) - Nhận thông báo cho sinh viên mình giới thiệu.
- **Nội dung thông báo:** Họ tên, Mã hồ sơ, Ngành học, Hệ đào tạo, CTV giới thiệu, Thời gian.

## 2. Thiết kế hệ thống
- **Công nghệ:** Laravel Notification, Telegram Bot API.
- **Cấu hình:** Lưu `TELEGRAM_BOT_TOKEN` và `TELEGRAM_ADMIN_CHAT_ID` trong file `.env`.
- **Dữ liệu:** 
    - Thêm `telegram_chat_id` vào bảng `users`.
    - Thêm các cột cấu hình thông báo Telegram vào bảng `notification_preferences`.

## 3. Danh mục tác vụ (Task List)

### Giai đoạn 1: Thiết lập hạ tầng
- [ ] Cài đặt thư viện: `composer require laravel-notification-channels/telegram`
- [ ] Cập nhật file `.env` và `config/services.php`.
- [ ] Tạo Migration thêm trường `telegram_chat_id` cho User.
- [ ] Tạo Migration thêm các trường cấu hình Telegram cho `notification_preferences`.

### Giai đoạn 2: Phát triển logic thông báo
- [ ] Tạo Notification class: `App\Notifications\StudentRegisteredNotification`.
- [ ] Cập nhật Model `User` và `NotificationPreference` để hỗ trợ channel Telegram.
- [ ] Tạo `StudentObserver` hoặc cập nhật logic trong `Student` model để kích hoạt notification sau khi record được tạo.

### Giai đoạn 3: Tích hợp giao diện quản lý
- [ ] Thêm trường nhập `Telegram Chat ID` trong trang cá nhân (Filament Profile).
- [ ] Cập nhật giao diện cài đặt thông báo (Notification Settings) để người dùng tự bật/tắt nhận tin qua Telegram.

### Giai đoạn 4: Kiểm thử & Hoàn thiện
- [ ] Test gửi tin nhắn tới Bot cá nhân.
- [ ] Test gửi tin nhắn tới Group.
- [ ] Đảm bảo tin nhắn định dạng Markdown đẹp mắt.

## 4. Xác nhận từ người dùng
Bạn hãy xác nhận giúp mình:
1. Bạn muốn mình cài đặt thư viện `laravel-notification-channels/telegram` ngay bây giờ không?
2. Bạn đã có Bot Token chưa, hay cần mình hướng dẫn tạo?
