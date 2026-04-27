# 04. Integrations & Security (Tích hợp & Bảo mật)

Tài liệu này mô tả cách hệ thống kết nối với bên ngoài và cách phân quyền truy cập.

## 1. Hệ thống Phân quyền (RBAC)

Hệ thống có các vai trò chính:
*   **Super Admin:** Toàn quyền hệ thống.
*   **Admin (Cán bộ tuyển sinh):** Quản lý học viên, duyệt hồ sơ, xem báo cáo tổng quát.
*   **Accountant (Kế toán):** Tập trung vào module Payment, xác nhận tiền, duyệt chi hoa hồng. Không được xóa học viên.
*   **Collaborator (Cộng tác viên):**
    *   Chỉ thấy học viên do mình giới thiệu.
    *   Chỉ thấy hoa hồng của chính mình.
    *   Được phép upload bill thanh toán cho sinh viên của mình.

**Bảo mật dữ liệu:**
*   Sử dụng **UUID** cho các link công khai để tránh bị dò quét (IDOR).
*   Chặn truy cập trực tiếp file ảnh nếu không có token hợp lệ.

## 2. Tích hợp Telegram (Notifications)

Hệ thống gửi thông báo tự động qua Telegram Bot cho CTV và Admin:
*   **Khi có học viên mới đăng ký:** Báo cho Admin và CTV liên quan.
*   **Khi thanh toán được xác nhận:** Báo cho CTV biết hoa hồng đã được ghi nhận.
*   **Khi hoa hồng chuyển sang trạng thái "Có thể thanh toán":** Thông báo để CTV kiểm tra và xác nhận nhận tiền.

**Cấu hình:** Mỗi CTV cần cung cấp `telegram_chat_id` trong hồ sơ cá nhân.

## 3. Lưu trữ Google Drive

Toàn bộ file (CCCD, Bằng cấp, Bill, Phiếu thu) được đẩy lên Google Drive để tiết kiệm bộ nhớ server và dễ dàng chia sẻ bộ phận hồ sơ.
*   **Cấu trúc thư mục:**
    *   `Hóa đơn đăng ký/{Năm}/{Mã hồ sơ}_{Tên}_{Ngành}_{Hệ}.png`
    *   `Phiếu thu/{Năm}/{Mã hồ sơ}_{Tên}_{Ngành}_{Hệ}.png`
    *   `Hồ sơ học viên/{Mã hồ sơ}/...`

## 4. Email (Resend)
Sử dụng dịch vụ Resend để gửi các thông báo quan trọng:
*   Xác nhận đăng ký thành công cho học viên.
*   Gửi thông tin tài khoản cho CTV mới.

---
> **Lưu ý cho AI Agent:**
> *   ERPNext có sẵn hệ thống **Role Permissions Manager** rất mạnh, hãy tận dụng nó.
> *   Sử dụng **Webhooks** hoặc **Frappe Hooks** để gửi thông báo Telegram.
> *   Cài đặt app **Frappe Google Drive** hoặc viết custom integration để đồng bộ file.
