# 01. Domain Model (Cấu trúc dữ liệu)

Tài liệu này mô tả các thực thể (Entities), trường dữ liệu (Fields) và mối quan hệ giữa chúng trong hệ thống CRM Liên thông. Đây là cơ sở để thiết lập các **DocTypes** trong ERPNext.

## 1. Học viên (Student)
Thực thể trung tâm của hệ thống.

*   **Thông tin chính:** `profile_code` (Mã hồ sơ - Duy nhất), `full_name`, `dob`, `phone` (Duy nhất), `email`, `identity_card` (CCCD).
*   **Hồ sơ học tập:** Thông tin tốt nghiệp Cao đẳng, Trung cấp, THPT (Trường, Ngành, Năm tốt nghiệp, Điểm GPA).
*   **Đăng ký liên thông:** `major` (Ngành học), `target_university` (Trường đích), `program_type` (Hệ đào tạo: Chính quy/VHVL/Từ xa), `intake_id` (Đợt nhập học).
*   **Trạng thái:**
    *   `status`: Mới -> Đã liên hệ -> Chờ xác minh -> Đã duyệt -> Đã nhập học -> Từ chối -> Bỏ học.
    *   `application_status`: Phân loại hồ sơ (Nháp, Thiếu giấy tờ, Đã nộp, Đủ điều kiện).
*   **Quan hệ:**
    *   BelongsTo `Collaborator` (Người giới thiệu).
    *   HasOne `Payment` (Thông tin đóng tiền).
    *   HasOne `Commission` (Thông tin hoa hồng).
    *   BelongsTo `Intake` (Khóa/Đợt).

## 2. Cộng tác viên (Collaborator)
Người giới thiệu học viên vào hệ thống.

*   **Thông tin chính:** `full_name`, `phone`, `email`, `tax_code` (Mã số thuế), `bank_name`, `bank_account`.
*   **Hệ thống Ref:** `ref_id` (Mã Ref định danh 8 ký tự), `telegram_chat_id` (Để nhận thông báo).
*   **Hệ thống Proxy:** Một Collaborator có thể có nhiều `RefCode` phụ (Proxy) để gán cho các nguồn khác nhau.
*   **Quan hệ:**
    *   HasMany `Student`.
    *   HasMany `CommissionItem` (Các khoản hoa hồng nhận được).
    *   HasMany `RefCode`.

## 3. Thanh toán (Payment)
Ghi nhận các khoản phí học viên đã nộp.

*   **Thông tin chính:** `amount` (Số tiền), `status` (Chưa nộp, Chờ xác minh, Đã xác nhận, Đã hoàn trả).
*   **Chứng từ:** `bill_path` (Ảnh bill CTV upload), `receipt_path` (Ảnh phiếu thu văn phòng upload), `receipt_number` (Số phiếu thu).
*   **Workflow:** Được xác nhận bởi `User` (Kế toán). Khi chuyển sang trạng thái "Đã xác nhận", hệ thống tự động kích hoạt tính toán hoa hồng.

## 4. Chính sách & Hoa hồng (Policy & Commission)

### Chính sách (CommissionPolicy)
Định nghĩa quy tắc chia tiền.
*   `target_program_id`: Áp dụng cho ngành nào (hoặc tất cả).
*   `program_type`: Áp dụng cho hệ đào tạo nào (CQ, VHVL, TX).
*   `payout_rules`: (JSON) Danh sách các dòng tiền sẽ sinh ra (Ai nhận, Bao nhiêu, Khi nào trả).

### Hoa hồng (Commission & CommissionItem)
*   `Commission`: Đại diện cho tổng hoa hồng của một đơn nộp (gắn với 1 Payment).
*   `CommissionItem`: Từng dòng tiền cụ thể.
    *   `recipient_collaborator_id`: Người nhận.
    *   `amount`: Số tiền.
    *   `trigger`: Điều kiện chi trả (`payment_verified` - trả ngay, `student_enrolled` - trả sau khi nhập học).
    *   `status`: Chờ nhập học, Có thể thanh toán, Đã thanh toán, Đã hủy.

## 5. Quản lý chỉ tiêu (Quota & Intake)
*   **Intake:** Các đợt tuyển sinh (Ví dụ: Đợt 1 - 2026).
*   **Quota:** Chỉ tiêu tuyển sinh cho từng ngành/hệ/đợt.

## 6. Ví & Giao dịch (Wallet & Transaction)
Theo dõi số dư và lịch sử thanh toán cho CTV (Tùy chọn mở rộng).

---
> **Lưu ý cho AI Agent:** Khi chuyển sang ERPNext, hãy sử dụng **DocType Links** để thể hiện các quan hệ này. Dùng **Naming Series** cho `profile_code` và `ref_id`.
