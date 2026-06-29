# Bài 6: Hướng dẫn về Kênh thông báo tự động Telegram

Tài liệu này hướng dẫn cách điền mã kết nối ứng dụng Telegram vào hệ thống để tự động gửi thông báo tuyển sinh và hoa hồng về Group/Channel Telegram của ban quản lý trường.

---

## 1. Điền mã liên kết Telegram vào hệ thống
Để kích hoạt gửi tin nhắn tự động, bạn cần điền các mã bảo mật Telegram vào file cấu hình môi trường `.env` ở thư mục gốc:

```env
TELEGRAM_BOT_TOKEN=nhap_ma_bot_telegram_tai_day
TELEGRAM_CHAT_ID=nhap_id_nhom_chat_telegram_tai_day
```

*   `TELEGRAM_BOT_TOKEN`: Mã token của Bot Telegram do BotFather cấp.
*   `TELEGRAM_CHAT_ID`: ID của Nhóm chat (Group) hoặc Kênh (Channel) Telegram nơi ban tuyển sinh của trường muốn nhận tin nhắn.

Sau khi sửa file cấu hình, chạy lệnh sau trên cửa sổ dòng lệnh để hệ thống nhận cấu hình mới:
```bash
php artisan config:clear
```

---

## 2. Các tin nhắn thông báo tự động trên Telegram

Khi hệ thống hoạt động, bot sẽ tự động gửi tin nhắn báo về Nhóm chat Telegram mỗi khi có các sự kiện sau xảy ra:

### a. Báo có học sinh đăng ký mới
Gửi ngay khi học sinh điền xong đơn trên trang web:
*   *Mẫu tin nhắn*: *"Thông báo: Học sinh Nguyễn Văn A vừa gửi đơn đăng ký học ngành CNTT - Hệ vừa học vừa làm. Mã hồ sơ: GTVT2026-PT-0002. Số điện thoại: 0912345678."*

![Hình 6.1: Tin nhắn Telegram tự động thông báo có học sinh mới vừa điền đơn đăng ký học](images/telegram_new_student_notification.png)

### b. Báo Kế toán duyệt tiền học phí thành công
Gửi khi kế toán xác nhận biên lai đóng phí của học sinh:
*   *Mẫu tin nhắn*: *"Thông báo: Đã xác nhận học phí thành công cho học sinh Nguyễn Văn A. Chỉ tiêu hiện tại của lớp học này là [5/50] học sinh."*

![Hình 6.2: Tin nhắn Telegram tự động thông báo kế toán đã xác thực đóng tiền học phí và số chỉ tiêu hiện tại](images/telegram_payment_verified_notification.png)

### c. Báo phát sinh hoa hồng cho Cộng tác viên
Gửi khi ví của CTV được cộng thêm tiền thưởng giới thiệu:
*   *Mẫu tin nhắn*: *"Thông báo: Số dư ví tích lũy của CTV Lê Trọng Đạt vừa được cộng thêm +750.000đ từ hồ sơ học sinh Nguyễn Văn A."*

![Hình 6.3: Tin nhắn Telegram tự động thông báo ví tiền CTV được cộng hoa hồng](images/telegram_commission_notification.png)

---

## 3. Lệnh chạy thử thông báo (Kiểm tra kết nối)
Để kiểm tra xem Bot Telegram đã kết nối thành công với nhóm chat chưa, bạn có thể chạy các lệnh thử nghiệm sau trong Terminal:

*   **Thử gửi thông báo học sinh mới**:
    ```bash
    php artisan test:student-notification
    ```
*   **Thử gửi thông báo duyệt học phí**:
    ```bash
    php artisan test:invoice-notification
    ```
*   **Thử gửi thông báo ví CTV**:
    ```bash
    php artisan test:commission-notification
    ```

Nếu nhóm chat nhận được tin nhắn thử nghiệm ngay lập tức, nghĩa là việc kết nối Telegram Bot đã thành công và hoạt động tốt.
