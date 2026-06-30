# Bài 6: Hướng dẫn cấu hình thông báo Telegram

Tài liệu này hướng dẫn cách kết nối Telegram Bot và kiểm tra việc nhận thông báo của hệ thống.

## 1. Cấu hình Bot Telegram

Hệ thống hiện dùng biến môi trường sau để kết nối bot:

```env
TELEGRAM_BOT_TOKEN=nhap_token_bot_tai_day
```

Sau khi thay đổi cấu hình, cần nạp lại cấu hình hệ thống để áp dụng.

## 2. Cấu hình người nhận thông báo

Hệ thống không dùng một mã nhận tin chung cho tất cả mọi người.

Thông báo Telegram phụ thuộc vào ID Telegram được lưu theo từng đối tượng:

- Người dùng nội bộ
- Cộng tác viên
- Mã ref phụ nếu có

Người dùng có thể khai báo ID Telegram trong hồ sơ cá nhân tại phần cấu hình Telegram.

Đối với CTV chính, có thể khai báo thêm các **nguồn phụ** và gán riêng ID Telegram cho từng nguồn phụ đó.

## 3. Các loại thông báo đang hỗ trợ

Tùy cấu hình từng tài khoản, hệ thống có thể gửi:

- Học viên đăng ký mới
- Có minh chứng chuyển khoản mới
- Thanh toán xác nhận
- Thanh toán bị từ chối
- Phát sinh hoa hồng mới

## 4. Bật hoặc tắt từng loại thông báo

Trong trang hồ sơ cá nhân, người dùng có thể bật hoặc tắt riêng các nhóm thông báo Telegram.

Ví dụ:

- Sinh viên đăng ký mới
- Minh chứng chuyển khoản
- Thanh toán xác nhận
- Thanh toán bị từ chối
- Nhận hoa hồng mới

## 5. Lệnh kiểm tra thông báo

Hệ thống hiện có 2 nhóm lệnh:

### a. Lệnh dùng trực tiếp trong Telegram Bot

- `/start`: bắt đầu sử dụng bot, đồng thời bot trả lại **ID Telegram** của bạn
- `/check`: xem báo cáo nhanh theo quyền của tài khoản Telegram đang dùng

Khi dùng `/start`, bot sẽ:

- Chào mừng bạn
- Nhắc dùng lệnh `/check`
- Trả về ID Telegram để bạn điền vào hệ thống
- Nhắc mẹo gửi bill bằng cách trả lời tin nhắn thông báo học viên mới

Khi dùng `/check`:

- Nếu là **CTV chính**, bạn nhận báo cáo tổng hợp của mình và các nguồn phụ
- Nếu là **CTV phụ**, bạn nhận báo cáo riêng của đúng nguồn phụ đó
- Nếu chưa được gán quyền, bot sẽ báo bạn gửi ID Telegram cho quản trị viên

### b. Lệnh kiểm tra kỹ thuật từ hệ thống

Đây là các lệnh chạy trong hệ thống để gửi thử thông báo:

```bash
php artisan notify:test-registration <chat_id>
php artisan notify:test-invoice <chat_id>
php artisan notify:test-commission <chat_id>
```

Trong đó:

- Lệnh thứ nhất dùng để gửi thử thông báo học viên đăng ký mới
- Lệnh thứ hai dùng để gửi thử thông báo minh chứng chuyển khoản
- Lệnh thứ ba dùng để gửi thử thông báo hoa hồng

## 6. CTV chính nhận được gì khi dùng `/check`

CTV chính nhận báo cáo tổng hợp gồm:

- Tổng số hồ sơ
- Tổng số tiền cộng dồn theo các hồ sơ thuộc mình và các nguồn phụ
- Phần hồ sơ trực tiếp của mình
- Từng nguồn phụ kèm số lượng hồ sơ theo từng hệ đào tạo

Nói ngắn gọn, đây là báo cáo để CTV chính theo dõi toàn bộ mạng lưới của mình.

## 7. CTV phụ nhận được gì khi dùng `/check`

CTV phụ nhận báo cáo riêng theo đúng **mã nguồn phụ** đã được gán ID Telegram.

Báo cáo hiện gồm:

- Số lượng hồ sơ hệ Chính quy
- Số lượng hồ sơ hệ Vừa học vừa làm
- Số lượng hồ sơ hệ Đào tạo từ xa
- Tổng số hồ sơ
- Danh sách 5 hồ sơ mới nhất
- Ghi chú thời điểm quyết toán theo từng hệ

CTV phụ không thấy tổng hợp của các nguồn khác và cũng không thấy toàn bộ mạng lưới của CTV chính.

## 8. Gửi bill bằng cách trả lời tin nhắn Telegram

Hệ thống hỗ trợ nộp bill nhanh qua Telegram như sau:

1. Chọn đúng tin nhắn thông báo học viên mới.
2. Bấm **trả lời** tin nhắn đó.
3. Gửi ảnh bill hoặc file ảnh bill.

Hệ thống sẽ tự:

- Nhận diện mã hồ sơ trong tin nhắn gốc
- Gắn bill vào đúng hồ sơ
- Chuyển hồ sơ sang trạng thái chờ xác minh
- Báo lại kết quả thành công hoặc lỗi

Các trường hợp bị từ chối:

- Không trả lời đúng tin nhắn hồ sơ
- Không tìm thấy mã hồ sơ
- Gửi file không phải ảnh
- Hồ sơ đã được xác nhận thanh toán rồi

## 9. CTV có thể nhận những loại thông báo nào

Tùy cấu hình trong hồ sơ cá nhân, người dùng có thể bật hoặc tắt:

- Sinh viên đăng ký mới
- Minh chứng chuyển khoản
- Thanh toán xác nhận
- Thanh toán bị từ chối
- Nhận hoa hồng mới

## 10. Lưu ý khi kiểm tra

- Cần dùng đúng ID Telegram đã khai báo trên hệ thống.
- Nếu không nhận được tin nhắn, kiểm tra lại `TELEGRAM_BOT_TOKEN`.
- Nếu vẫn chưa nhận được, kiểm tra người dùng đã bật đúng nhóm thông báo hay chưa.
