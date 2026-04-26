# Báo cáo Audit Bảo mật Hệ thống CRM (Laravel + PostgreSQL)

## 1. Executive Summary
Hệ thống CRM hiện tại được xây dựng trên nền tảng Laravel 11 và PostgreSQL với các biện pháp bảo mật cơ bản khá tốt. Đặc biệt, các lỗ hổng nghiêm trọng như IDOR trên file công khai và tính dự đoán của ID hồ sơ đã được xử lý bằng UUID và Secure Tokens. Tuy nhiên, vẫn tồn tại một số điểm yếu về cấu hình hạ tầng, chính sách mật khẩu và phạm vi ghi nhật ký (logging) có thể gây rủi ro cho dữ liệu production.

**Điểm đánh giá tổng thể: 7.5/10 (Khá)**
- **Rủi ro cao nhất:** Chính sách mật khẩu yếu cho CTV và thiếu CSP.
- **Mức độ ưu tiên:** Cần thắt chặt cấu hình Database và hoàn thiện hệ thống Audit Log.

---

## 2. Security Checklist & Status

| Danh mục | Trạng thái | Đánh giá | Ghi chú |
| :--- | :---: | :--- | :--- |
| **Laravel Security** | ✅ | Tốt | APP_DEBUG tắt, CSRF bật, có Security Headers cơ bản. |
| **Authentication** | ⚠️ | Trung bình | Google Login tốt, nhưng mật khẩu mặc định/độ dài yếu. |
| **Authorization** | ✅ | Tốt | Phân quyền Spatie RBAC, có scoping query theo role. |
| **PostgreSQL** | ⚠️ | Cần cải thiện | SSL mode đang là `prefer`, nên dùng `require`. |
| **Input/OWASP** | ✅ | Tốt | Dùng UUID, reCAPTCHA, Token-based file access. |
| **API Security** | ✅ | Tốt | Rate limiting, Webhook CSRF exception an toàn. |
| **Business Logic** | ✅ | Tốt | Có cơ chế đối soát tiền thừa, chuyển hệ tự động. |
| **Infrastructure** | ⚠️ | Trung bình | Thiếu Content Security Policy (CSP). |
| **Logging/Audit** | ⚠️ | Cần mở rộng | Audit log bị giới hạn theo event group, bỏ sót các thay đổi khác. |

---

## 3. Chi tiết lỗ hổng & Đề xuất sửa đổi

### 3.1. Chính sách mật khẩu CTV yếu
- **Mô tả:** `CollaboratorForm.php` đặt mật khẩu mặc định là `123456` và chỉ yêu cầu tối thiểu 6 ký tự.
- **Impact:** Dễ bị tấn công Brute-force hoặc chiếm quyền tài khoản nếu CTV không đổi pass.
- **Likelihood:** Cao.
- **Fix:** 
    - Tăng `minLength(8)` hoặc yêu cầu `passwordDefaults()`.
    - Không đặt mật khẩu mặc định dễ đoán, nên dùng `Str::random(12)` và gửi mail.

### 3.2. Cấu hình Database chưa tối ưu (SSL mode)
- **Mô tả:** Trong `config/database.php`, `sslmode` đặt là `prefer`.
- **Impact:** Có thể bị tấn công Man-in-the-Middle (MitM) nếu kết nối giữa App và DB không bắt buộc mã hóa.
- **Likelihood:** Thấp (nếu chạy cùng mạng nội bộ) nhưng rủi ro trên Cloud.
- **Fix:** Đổi sang `sslmode => 'require'` hoặc `verify-full` (nếu có CA).

### 3.3. Thiếu Content Security Policy (CSP)
- **Mô tả:** Middleware `SecurityHeaders` chưa có header `Content-Security-Policy`.
- **Impact:** Rủi ro tấn công XSS (Cross-Site Scripting) và Clickjacking.
- **Likelihood:** Trung bình.
- **Fix:** Bổ sung CSP header, giới hạn các nguồn script/style tin cậy (Google, internal).

### 3.4. Phạm vi Audit Log hạn chế
- **Mô tả:** Trait `HasAuditLog.php` lọc log theo group `financial` và `account_deletion`.
- **Impact:** Mất dấu vết nếu có sự thay đổi dữ liệu hồ sơ sinh viên (không phải tiền) hoặc thay đổi cấu hình hệ thống bởi admin.
- **Likelihood:** Trung bình.
- **Fix:** Mở rộng ghi log cho tất cả các thay đổi quan trọng trên `Student`, `User`, `Quota`.

---

## 4. Quick Wins (Sửa nhanh trong 30p)
1. **Database:** Cập nhật `sslmode` trong `.env` hoặc config.
2. **Password:** Tăng `minLength(8)` trong `CollaboratorForm.php`.
3. **Security Headers:** Bổ sung `Referrer-Policy` và `Permissions-Policy` vào middleware.

---

## 5. Priority Roadmap

### Giai đoạn 1: Immediate Fixes (Tuần 1) - ✅ ĐÃ HOÀN THÀNH
- [x] Nâng cấp chính sách mật khẩu (độ dài, độ phức tạp).
- [x] Ép buộc SSL cho kết nối PostgreSQL.
- [x] Cấu hình CSP cơ bản để chặn inline script không rõ nguồn gốc.

### Giai đoạn 2: Hardening (Tháng 1) - ✅ ĐÃ HOÀN THÀNH
- [x] Mở rộng hệ thống Audit Log cho toàn bộ các model chính.
- [x] Triển khai `spatie/laravel-csp` để quản lý CSP linh hoạt hơn.
- [x] Rà soát lại phân quyền (Permissions) để đảm bảo Principle of Least Privilege.

### Giai đoạn 3: Monitoring (Dài hạn)
- [ ] Setup cảnh báo Telegram khi có hành vi login bất thường hoặc brute-force.
- [ ] Định kỳ scan dependency (`composer audit`, `npm audit`).
- [ ] Backup database định kỳ lên Google Drive (đã có cơ chế, cần verify tính sẵn sàng).

---
**Người thực hiện:** Antigravity Security Auditor
**Ngày báo cáo:** 26/04/2026
