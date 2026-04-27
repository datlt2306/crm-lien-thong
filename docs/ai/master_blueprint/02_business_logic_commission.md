# 02. Business Logic: Commission (Thuật toán hoa hồng)

Đây là phần phức tạp nhất của hệ thống, xử lý việc tính toán và phân bổ tiền cho các Cộng tác viên.

## 1. Cơ chế khớp chính sách (Policy Matching)
Khi một khoản thanh toán (`Payment`) được xác nhận, hệ thống tìm kiếm chính sách (`CommissionPolicy`) phù hợp nhất theo thứ tự ưu tiên:
1.  **Collaborator cụ thể:** Chính sách dành riêng cho một CTV (Độ ưu tiên cao nhất).
2.  **Ngành học cụ thể (`target_program_id`):** Chính sách dành cho ngành học của sinh viên.
3.  **Hệ đào tạo (`program_type`):** Chính sách dành cho hệ CQ, VHVL, hoặc Từ xa.
4.  **Mặc định:** Nếu không khớp cái nào, dùng giá trị fallback cứng (Ví dụ: CQ = 1.75tr, VHVL = 750k).

## 2. Quy tắc chia tiền (Payout Rules)
Mỗi chính sách chứa một cấu trúc JSON định nghĩa các dòng hoa hồng sẽ sinh ra.
*   **Người nhận (`recipient_type`):**
    *   `direct_ctv`: Người trực tiếp giới thiệu sinh viên.
    *   `specific_ctv`: Một người cụ thể (thường là cấp quản lý hoặc người hỗ trợ).
*   **Thời điểm chi trả (`payout_trigger`):**
    *   `payment_verified`: Cho phép trả ngay sau khi văn phòng xác nhận nhận tiền (thường vào mùng 5 hàng tháng).
    *   `student_enrolled`: Chỉ cho phép trả sau khi sinh viên đã hoàn tất thủ tục nhập học chính thức.

## 3. Vòng đời của một dòng hoa hồng (CommissionItem Status)
1.  **`pending` (Chờ nhập học):** Dòng tiền đã sinh ra nhưng chưa đủ điều kiện chi trả (do trigger là `student_enrolled`).
2.  **`payable` (Có thể thanh toán):** Đã đủ điều kiện, đang chờ kế toán lập lệnh chi.
3.  **`payment_confirmed` (Đã chốt & Đã chi):** Kế toán đã chuyển khoản và upload minh chứng (Bill).
4.  **`received_confirmed` (CTV đã nhận):** Cộng tác viên xác nhận đã nhận được tiền trên giao diện của họ.
5.  **`cancelled` (Đã hủy):** Bị hủy do sinh viên rút hồ sơ hoặc chuyển hệ.

## 4. Xử lý Chuyển hệ (Recalculation on Transfer)
Khi sinh viên chuyển hệ đào tạo (ví dụ từ CQ sang VHVL):
1.  Hệ thống tìm kiếm chính sách mới tương ứng với hệ vừa chuyển.
2.  So sánh các dòng hoa hồng cũ và mới:
    *   Nếu dòng tiền cũ **chưa chi trả**: Cập nhật số tiền mới hoặc Hủy nếu không còn trong chính sách mới.
    *   Nếu dòng tiền cũ **đã chi trả**: Giữ nguyên để đảm bảo tính minh bạch kế toán, đánh dấu là `is_adjusted`.
3.  Sinh thêm các dòng tiền mới nếu chính sách mới yêu cầu.

## 5. Mở khóa hoa hồng (Unlocking)
Khi trạng thái sinh viên chuyển sang `STATUS_ENROLLED`:
*   Hệ thống quét toàn bộ `CommissionItem` có trigger là `student_enrolled` và trạng thái `pending`.
*   Chuyển chúng sang `payable` để đưa vào danh sách chi trả tháng tiếp theo.

---
> **Lưu ý cho AI Agent:** Trong ERPNext, logic này nên được cài đặt trong một **Server Script** gắn với DocType `Payment Entry` (on_submit) hoặc `Sales Invoice`. Hãy sử dụng **Background Jobs** cho việc tính toán lại hoa hồng khi chuyển hệ để tránh treo giao diện.
