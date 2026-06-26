# 09-edge-cases.md - Xử lý trường hợp ngoại lệ & Biên nghiệp vụ

## 1. Nghiệp vụ chuyển đổi Ngành học / Hệ đào tạo (Major & Program Transfer)
* **Quy trình thực tế:** Sinh viên đã đăng ký học hệ đào tạo A (ví dụ: Chính quy) nhưng muốn đổi sang hệ đào tạo B (ví dụ: Vừa học vừa làm) hoặc đổi ngành học.
* **Xử lý số lượng Chỉ tiêu (Quota):**
  * Hệ thống giải phóng chỉ tiêu cũ (giảm `current_quota` hoặc `pending_quota` tùy thuộc trạng thái thanh toán cũ).
  * Tiêu thụ chỉ tiêu của ngành học/hệ đào tạo mới (tăng `current_quota` hoặc `pending_quota` tương ứng).
  * Đồng bộ lại chỉ tiêu năm `AnnualQuota`.
* **Tính toán lại Hoa hồng CTV:**
  * Khởi chạy giao dịch tính toán lại hoa hồng (`recalculateCommissionOnTransfer`).
  * Tìm kiếm chính sách hoa hồng mới.
  * Nếu hoa hồng cũ **chưa được chi trả** (trạng thái pending, payable, hoặc cancelled), hệ thống hủy bản ghi cũ và sinh dòng hoa hồng mới theo luật mới.
  * Nếu hoa hồng cũ **đã được chi trả** (payment_confirmed hoặc received_confirmed), hệ thống **giữ nguyên dòng tiền cũ** để tránh thất thoát hoặc lệch đối soát thủ công, đồng thời đánh dấu `is_adjusted = true` để kế toán kiểm tra thủ công chênh lệch học phí.

### Evidence:
* Hàm điều phối chuyển ngành Quota: [QuotaService.php:L132-177](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/QuotaService.php#L132-L177)
* Logic tính lại hoa hồng khi chuyển ngành: [CommissionService.php:L259-388](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/CommissionService.php#L259-L388)
* Đánh dấu điều chỉnh hoa hồng: [CommissionService.php:L337](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/CommissionService.php#L337)

---

## 2. Hoàn tiền cho sinh viên rút hồ sơ (Refund & Reversal Procedure)
* **Quy trình thực tế:** Sinh viên nộp tiền nhưng sau đó không đủ điều kiện tuyển sinh hoặc chủ động xin rút hồ sơ, kế toán thực hiện hoàn trả lại học phí.
* **Quy tắc giải phóng Quota:**
  * Gọi hàm `restoreQuotaOnPaymentReverted()`.
  * Chuyển chỉ tiêu thực tế từ `current_quota` ngược về chỉ tiêu chờ duyệt `pending_quota` (hoặc giảm hoàn toàn tùy theo trạng thái hủy hồ sơ).
  * Giảm tương ứng chỉ tiêu năm `AnnualQuota`.
* **Hủy Hoa hồng CTV:**
  * Các dòng hoa hồng chưa chi trả (pending, payable) sẽ được cập nhật trạng thái thành Đã hủy (`cancelled`).
  * CTV sẽ không còn nhìn thấy các dòng hoa hồng này khả dụng để rút tiền.

### Evidence:
* Hoàn trả chỉ tiêu: [QuotaService.php:L70-115](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/QuotaService.php#L70-L115)
* Hủy hoa hồng dư thừa: [CommissionService.php:L364-372](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/CommissionService.php#L364-L372)

---

## 3. Khóa biên chỉ tiêu (Quota Overflow)
* **Quy tắc biên:** Mỗi đợt tuyển sinh có một mức giới hạn tối đa `target_quota`.
* **Ngăn chặn ghi danh vượt hạn:**
  * Khi sinh viên đăng ký qua link ref, hệ thống kiểm tra `available_slots` (được tính bằng `target_quota` - (`current_quota` + `pending_quota`)).
  * Nếu số lượng slot còn lại `<= 0`, hệ thống ném ngoại lệ Validation chặn không cho tạo hồ sơ mới, đồng thời tự động cập nhật trạng thái Quota thành Đã đầy (`full`).
* **Tránh Race Condition đăng ký đồng thời:** Hệ thống thực hiện khóa bản ghi `lockForUpdate()` trên Quota được chọn trước khi kiểm tra số slot trống, đảm bảo hai sinh viên đăng ký cùng một giây cuối cùng không thể làm tràn chỉ tiêu tối đa của đợt.

### Evidence:
* Tính toán slots còn trống: [Quota.php:L83-85](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/Quota.php#L83-L85)
* Lock kiểm tra quota tràn: [PublicStudentController.php:L140-142](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Http/Controllers/PublicStudentController.php#L140-L142)
* Tự động khóa Quota khi đầy: [Quota.php:L108-110](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/Quota.php#L108-L110)
