# 06-finance.md - Nghiệp vụ Tài chính & Quản lý dòng tiền

## 1. Định mức Lệ phí & Học phí (Expected Tuition Fees)
Học phí/lệ phí được tính toán tự động dựa trên ngành học, đợt tuyển và hệ đào tạo.
* **Cơ chế tính toán:** Ưu tiên số một là đọc trực tiếp giá trị `tuition_fee` cấu hình trong bảng chỉ tiêu `quotas`.
* **Fallback hệ đào tạo:** Nếu quota chưa cấu hình giá trị học phí cụ thể, hệ thống sẽ sử dụng mức học phí mặc định:
  * **Hệ chính quy (regular):** `1.750.000 VNĐ`
  * **Hệ vừa học vừa làm (part_time):** `750.000 VNĐ`
  * **Hệ đào tạo từ xa (distance):** `200.000 VNĐ`

### Evidence:
* Dịch vụ tính toán học phí: [StudentFeeService.php:L17-58](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/StudentFeeService.php#L17-L58)
* Mức giá trị mặc định: [StudentFeeService.php:L32-36](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/StudentFeeService.php#L32-L36)

---

## 2. Quy tắc Chia tách & Phân phối Hoa hồng (Commission Splits)
Khi Kế toán xác minh thanh toán (`verify_payment`), hệ thống sẽ kích hoạt tính toán hoa hồng.
1. **Tìm kiếm Chính sách:** Đọc chính sách hoa hồng (`CommissionPolicy`) khớp nhất.
2. **Quy tắc Phân tách (Payout Rules):** 
   * Nếu chính sách có trường `payout_rules` dạng JSON cấu hình sẵn (ví dụ: chia cho CTV trực tiếp và CTV cấp quản lý), hệ thống duyệt mảng và tạo các `CommissionItem` tương ứng.
   * Nếu không cấu hình rules chia tách, hệ thống áp dụng cơ chế Hoa hồng mặc định (Fallback) chuyển thẳng cho CTV trực tiếp:
     * Chính quy: `1.750.000 VNĐ`
     * Vừa học vừa làm: `750.000 VNĐ`
     * Từ xa: `200.000 VNĐ`
3. **Mở khóa hoa hồng theo điều kiện (Trigger):**
   * **`payment_verified` (Mùng 5 hàng tháng):** Đánh dấu trạng thái hoa hồng là Có thể thanh toán (`payable`) ngay lập tức.
   * **`student_enrolled` (Sau khi nhập học):** Trạng thái hoa hồng ban đầu là Chờ nhập học (`pending`). Khi trạng thái sinh viên cập nhật sang `enrolled`, `CommissionService` sẽ quét và mở khóa toàn bộ dòng tiền này sang `payable`.

### Evidence:
* Logic phân tách hoa hồng: [CommissionService.php:L18-55](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/CommissionService.php#L18-L55)
* Hàm tạo dòng tiền mặc định: [CommissionService.php:L60-94](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/CommissionService.php#L60-L94)
* Áp dụng quy tắc Payout Rules: [CommissionService.php:L99-145](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/CommissionService.php#L99-L145)
* Quyết toán và chuyển trạng thái: [CommissionService.php:L216-223](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/CommissionService.php#L216-L223)

---

## 3. Quản lý Ví CTV & Ngăn chặn Race Condition
* **Tương tác Ví & Biến động số dư:**
  * CTV được cấp một Ví (`Wallet`) quản lý số dư khả dụng (`balance`), tổng số tiền đã nhận (`total_received`) và tổng số tiền đã chi (`total_paid`).
  * Mọi hoạt động nạp, rút hoặc đối soát đều tạo ra một giao dịch ví (`WalletTransaction`) lưu vết số dư trước/sau biến động (`balance_before`, `balance_after`).
* **Phòng chống Race Condition (Double Payout):**
  * Trong các hoạt động nhạy cảm như CTV bấm Xác nhận đã nhận tiền hoa hồng (`confirmDirectReceived()`), hệ thống áp dụng cơ chế khóa dữ liệu cấp cơ sở dữ liệu `lockForUpdate()` trên chính dòng `CommissionItem` đó.
  * Việc này đảm bảo tại một thời điểm chỉ có duy nhất một luồng xử lý được thay đổi trạng thái hoa hồng, ngăn chặn việc gọi trùng lặp dẫn đến cộng tiền Ví CTV hai lần.

### Evidence:
* Lock hàng phòng Race Condition: [CommissionService.php:L230-238](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/CommissionService.php#L230-L238)
* Model Wallet & Casts: [Wallet.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/Wallet.php)
* Model WalletTransaction: [WalletTransaction.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/WalletTransaction.php)
