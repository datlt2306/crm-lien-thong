# 09-edge-cases.md - Xử lý trường hợp ngoại lệ & Biên nghiệp vụ

## 1. Nghiệp vụ chuyển đổi Ngành học / Hệ đào tạo (Major & Program Transfer)
*   **Quy trình thực tế:** Sinh viên đã đăng ký học hệ đào tạo A (ví dụ: Chính quy) nhưng muốn đổi sang hệ đào tạo B (ví dụ: Vừa học vừa làm) hoặc đổi ngành học.
*   **Xử lý số lượng Chỉ tiêu (Quota):**
    *   Hệ thống giải phóng chỉ tiêu cũ (giảm `current_quota` hoặc `pending_quota` tùy thuộc trạng thái thanh toán cũ).
    *   Tiêu thụ chỉ tiêu của ngành học/hệ đào tạo mới (tăng `current_quota` hoặc `pending_quota` tương ứng).
    *   Đồng bộ lại chỉ tiêu năm `AnnualQuota`.
*   **Xử lý chênh lệch tài chính học phí trên Sổ cái:**
    *   Thay vì sửa đè bản ghi phiếu thu (`PaymentReceipt`), hệ thống tính toán chênh lệch giữa mức học phí của hệ cũ ($T_1$) và hệ mới ($T_2$).
    *   Tạo bản ghi `adjustment` trong `StudentLedger` với giá trị $T_{diff} = T_1 - T_2$.
    *   *Sinh viên đóng dư tiền ($T_1 > T_2$):* Số dư `balance_after` của sinh viên chuyển sang dương. Số tiền này có thể hoàn trả lại hoặc giữ lại cấn trừ lệ phí khác.
    *   *Sinh viên nợ thêm tiền ($T_1 < T_2$):* Số dư `balance_after` chuyển sang âm (nợ). Sinh viên/CTV phải tải lên một `PaymentReceipt` bổ sung cho phần chênh lệch này.
*   **Tính toán lại Hoa hồng CTV:**
    *   Nếu hoa hồng cũ **chưa được chi trả** (trạng thái pending, payable, hoặc cancelled), hệ thống hủy bản ghi cũ (chuyển sang `cancelled`) và sinh dòng hoa hồng mới theo luật mới.
    *   Nếu hoa hồng cũ **đã được chi trả** (payment_confirmed hoặc received_confirmed), hệ thống **giữ nguyên dòng tiền cũ** để tránh lệch đối soát, đồng thời tạo một bản ghi `CommissionItem` điều chỉnh với giá trị âm (trừ chênh lệch hoa hồng) trỏ trực tiếp đến CTV đó và tự động ghi nhận giao dịch ví trừ tiền ví CTV.

---

## 2. Hoàn tiền cho sinh viên rút hồ sơ (Refund & Reversal Procedure)
*   **Quy trình thực tế:** Sinh viên nộp tiền nhưng sau đó không đủ điều kiện tuyển sinh hoặc chủ động xin rút hồ sơ, kế toán thực hiện hoàn trả lại học phí.
*   **Quy tắc giải phóng Quota:**
    *   Giảm chỉ tiêu thực tế tuyển sinh (`current_quota` và chỉ chỉ tiêu năm `AnnualQuota`).
*   **Hạch toán Hoàn trả trên Ledger:**
    *   Kế toán phê duyệt số tiền hoàn trả lại cho sinh viên.
    *   Tạo dòng giao dịch `type = refund` trong `StudentLedger` tương ứng với số tiền hoàn thực tế để đưa số dư công nợ của học viên về `0`.
*   **Thu hồi Hoa hồng CTV:**
    *   Các dòng hoa hồng chưa chi trả (pending, payable) sẽ được cập nhật trạng thái thành Đã hủy (`cancelled`).
    *   Các dòng hoa hồng đã chi trả: Phát sinh dòng điều chỉnh `CommissionItem` âm tương ứng và tự động trừ số dư Ví khả dụng (`Wallet`) của CTV qua `WalletTransaction` ghi nhận biến động âm.

---

## 3. Khóa biên chỉ tiêu (Quota Overflow)
*   **Quy tắc biên:** Mỗi đợt tuyển sinh có một mức giới hạn tối đa `target_quota`.
*   **Ngăn chặn ghi danh vượt hạn:**
    *   Khi sinh viên đăng ký qua link ref, hệ thống kiểm tra `available_slots` (được tính bằng `target_quota` - (`current_quota` + `pending_quota`)).
    *   Nếu số lượng slot còn lại `<= 0`, hệ thống ném ngoại lệ Validation chặn không cho tạo hồ sơ mới, đồng thời tự động cập nhật trạng thái Quota thành Đã đầy (`full`).
*   **Tránh Race Condition đăng ký đồng thời:** Hệ thống thực hiện khóa bản ghi `lockForUpdate()` trên Quota được chọn trước khi kiểm tra số slot trống, đảm bảo hai sinh viên đăng ký cùng một giây cuối cùng không thể làm tràn chỉ tiêu tối đa của đợt.
