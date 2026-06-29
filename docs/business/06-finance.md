# 06-finance.md - Nghiệp vụ Tài chính & Quản lý dòng tiền

## 1. Định mức Lệ phí & Học phí (Expected Tuition Fees)
Học phí/lệ phí được tính toán tự động dựa trên ngành học, đợt tuyển và hệ đào tạo.
*   **Cơ chế tính toán:** Ưu tiên số một là đọc trực tiếp giá trị `tuition_fee` cấu hình trong bảng chỉ tiêu `quotas`.
*   **Fallback hệ đào tạo:** Nếu quota chưa cấu hình giá trị học phí cụ thể, hệ thống sẽ sử dụng mức học phí mặc định:
    *   **Hệ chính quy (regular):** `1.750.000 VNĐ`
    *   **Hệ vừa học vừa làm (part_time):** `750.000 VNĐ`
    *   **Hệ đào tạo từ xa (distance):** `200.000 VNĐ`

---

## 2. Ghi nhận Tài chính qua Sổ cái Công nợ (Student Ledger Accounting)
Hệ thống áp dụng nguyên tắc **Sổ cái Công nợ (`StudentLedger`)** bất biến để xử lý dòng tiền của sinh viên:

### 2.1 Phát sinh Công nợ (Debit)
Khi hồ sơ sinh viên được tạo và gán chỉ tiêu tuyển sinh (`quota_id`), hệ thống tự động ghi nhận một dòng `debit` tương đương với mức học phí phải nộp.
*   *Ví dụ:* Sinh viên A đăng ký hệ Chính quy -> Ghi Ledger: `type = debit`, `amount = 1,750,000đ`, `balance_after = -1,750,000đ`.

### 2.2 Ghi nhận Thanh toán (Credit)
Khi sinh viên nộp minh chứng chuyển khoản (`PaymentReceipt` được verify), hệ thống ghi nhận một dòng `credit` để cấn trừ vào số dư nợ của sinh viên.
*   *Ví dụ:* Nộp phiếu thu 1,750,000đ -> Ghi Ledger: `type = credit`, `amount = 1,750,000đ`, `balance_after = 0đ`.

### 2.3 Điều chỉnh Công nợ (Adjustment)
Khi sinh viên chuyển ngành/hệ đào tạo, hoặc được hưởng chính sách miễn giảm, kế toán phát sinh một dòng `adjustment` để thay đổi định mức nợ mà không được sửa đổi lịch sử các dòng Debit/Credit cũ.
*   *Ví dụ:* Sinh viên chuyển từ Chính quy (1.75M) sang VLVH (750k) -> Ghi Ledger: `type = adjustment`, `amount = 1,000,000đ` (Cộng ngược lại tài khoản học viên), `balance_after = +1,000,000đ` (Sinh viên đóng thừa).

---

## 3. Quy tắc Chia tách & Phân phối Hoa hồng (Commission Splits)
Khi Kế toán xác minh thanh toán (`verify_payment` trên `PaymentReceipt`), hệ thống sẽ kích hoạt tính toán hoa hồng.
1.  **Tìm kiếm Chính sách:** Đọc chính sách hoa hồng (`CommissionPolicy`) khớp nhất.
2.  **Quy tắc Phân tách (Payout Rules):** 
    *   Nếu chính sách có trường `payout_rules` dạng JSON cấu hình sẵn (ví dụ: chia cho CTV trực tiếp và CTV cấp quản lý), hệ thống duyệt mảng và tạo các `CommissionItem` tương ứng.
    *   Nếu không cấu hình rules chia tách, hệ thống áp dụng cơ chế Hoa hồng mặc định (Fallback) chuyển thẳng cho CTV trực tiếp:
        *   Chính quy: `1.750.000 VNĐ`
        *   Vừa học vừa làm: `750.000 VNĐ`
        *   Từ xa: `200.000 VNĐ`
3.  **Mở khóa hoa hồng theo điều kiện (Trigger):**
    *   **`payment_verified` (Mùng 5 hàng tháng):** Đánh dấu trạng thái hoa hồng là Có thể thanh toán (`payable`) ngay lập tức.
    *   **`student_enrolled` (Sau khi nhập học):** Trạng thái hoa hồng ban đầu là Chờ nhập học (`pending`). Khi trạng thái sinh viên cập nhật sang `enrolled`, `CommissionService` sẽ quét và mở khóa toàn bộ dòng tiền này sang `payable`.

---

## 4. Quản lý Ví CTV & Biến động Ví
*   **Tương tác Ví & Biến động số dư:**
    *   CTV được cấp một Ví (`Wallet`) quản lý số dư khả dụng (`balance`), tổng số tiền đã nhận (`total_received`) và tổng số tiền đã chi (`total_paid`).
    *   Mọi hoạt động nạp, rút hoặc thu hồi hoa hồng do sinh viên rút hồ sơ đều tạo ra một giao dịch ví (`WalletTransaction`) lưu vết số dư trước/sau biến động (`balance_before`, `balance_after`).
*   **Phòng chống Race Condition (Double Payout):**
    *   Trong các hoạt động nhạy cảm như CTV bấm Xác nhận đã nhận tiền hoa hồng (`confirmDirectReceived()`), hệ thống áp dụng cơ chế khóa dữ liệu cấp cơ sở dữ liệu `lockForUpdate()` trên chính dòng `CommissionItem` đó.
    *   Việc này đảm bảo tại một thời điểm chỉ có duy nhất một luồng xử lý được thay đổi trạng thái hoa hồng, ngăn chặn việc gọi trùng lặp dẫn đến cộng tiền Ví CTV hai lần.
