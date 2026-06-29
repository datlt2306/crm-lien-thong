# Bài 5: Hướng dẫn dành cho Quản trị viên (Ban Quản lý hệ thống)

Tài liệu này hướng dẫn ban quản trị thiết lập các đợt tuyển sinh, số lượng chỉ tiêu ngành, cài đặt chính sách chi trả hoa hồng, chuyển ngành/hệ học cho học sinh, khôi phục hồ sơ và cấu hình thời gian giữ lead.

---

## 1. Thiết lập Đợt tuyển sinh mới & Giới hạn chỉ tiêu

### Tạo Đợt tuyển sinh mới:
1.  Vào mục **Đợt tuyển sinh (Intakes)** $\rightarrow$ Bấm **Tạo mới**.
    ![Hình 5.1: Giao diện tạo Đợt tuyển sinh mới với thông tin ngày bắt đầu/kết thúc](images/admin_create_intake.png)
2.  Điền Tên đợt (Ví dụ: *Đợt xét tuyển tháng 6/2026*), chọn ngày bắt đầu, ngày kết thúc nhận hồ sơ và đặt trạng thái là `active` (Đang mở).

### Cấu hình Chỉ tiêu nhận hồ sơ theo từng ngành:
Mỗi đợt tuyển sinh phải giới hạn số lượng học sinh tối đa có thể đăng ký cho từng Ngành học và Hệ đào tạo:
1.  Vào mục **Chỉ tiêu (Quotas)** $\rightarrow$ **Tạo mới**.
    ![Hình 5.2: Form thiết lập chỉ tiêu số lượng giới hạn và mức phí của từng ngành học](images/admin_create_quota.png)
2.  Chọn Đợt tuyển sinh, nhập tên Ngành học, chọn Hệ đào tạo (Chính quy, Vừa học vừa làm, hoặc Từ xa).
3.  Nhập **Chỉ tiêu tối đa** (Ví dụ: 10 học sinh). Khi số lượng học sinh đăng ký cộng với số học sinh đã đóng tiền vượt quá con số này, hệ thống sẽ tự động khóa trang đăng ký của ngành học đó lại để tránh bị quá tải chỉ tiêu của trường.
4.  Nhập mức học phí của ngành/hệ học đó.

---

## 2. Cài đặt Chính sách hoa hồng cho CTV
Hệ thống tự động tính tiền cho CTV dựa trên quy tắc bạn cài đặt:

1.  Vào mục **Chính sách hoa hồng (Commission Policies)** $\rightarrow$ **Tạo mới**.
    ![Hình 5.3: Form cài đặt chính sách hoa hồng tự động phân chia theo hệ học](images/admin_commission_policy.png)
2.  Thiết lập:
    *   **CTV áp dụng**: Chọn một CTV cụ thể (Ví dụ: Lê Trọng Đạt) để làm chính sách đặc biệt cho họ, hoặc để trống để áp dụng chung cho tất cả các CTV khác.
    *   **Hệ học áp dụng**: Chọn hệ Chính quy, VHVL hay Từ xa.
    *   **Mức tiền chi trả**: Nhập số tiền và điều kiện mở khóa tiền:
        *   *Ví dụ hệ Chính quy*: Điền số tiền **1.750.000đ**, chọn điều kiện mở khóa là *"Khi kế toán duyệt tiền"* (`payment_verified`).
        *   *Ví dụ hệ VHVL/Từ xa*: 
            *   Dòng 1: Điền **750.000đ**, điều kiện mở khóa là *"Khi kế toán duyệt tiền"*.
            *   Dòng 2: Điền **1.450.000đ**, điều kiện mở khóa là *"Khi học viên nhập học thành công"* (`student_enrolled`).

---

## 3. Chuyển đổi Ngành học hoặc Hệ đào tạo cho học sinh (Student Transfer)
Khi học sinh đã nộp hồ sơ hoặc đóng học phí nhưng xin đổi sang ngành học hoặc hệ đào tạo khác:

1.  Vào mục **Học viên (Students)**, mở chi tiết học sinh cần đổi hệ.
2.  Nhấp vào nút **Chuyển hệ (Transfer Program)** trên thanh công cụ.
    ![Hình 5.4: Form thao tác chuyển đổi ngành học/hệ đào tạo cho học sinh](images/admin_student_transfer.png)
3.  Trong bảng lựa chọn hiện ra:
    *   Chọn Ngành học/Hệ đào tạo mới mà học sinh muốn chuyển sang.
    *   Nhập lý do chuyển đổi.
4.  Nhấn xác nhận. Hệ thống sẽ tự động chuyển chỉ tiêu của học sinh sang ngành mới, tạo bảng tính toán học phí thừa/thiếu cho học sinh, tính lại tiền hoa hồng cho CTV giới thiệu và khấu trừ vào ví của họ nếu hệ học mới có mức hoa hồng thấp hơn hệ cũ.

---

## 4. Khôi phục hồ sơ học viên cũ (Restore Profile)
Khi học sinh đã rút hồ sơ hoặc bị từ chối trước đó nhưng nay xin quay lại học tiếp:

1.  Tìm kiếm tên học sinh đó trong danh sách quản lý.
2.  Chỉnh sửa hồ sơ, chuyển trạng thái của học sinh về trạng thái hoạt động bình thường (Ví dụ: Đăng ký mới, Đã duyệt hồ sơ, hoặc Đã nhập học).
3.  Khi lưu lại, hệ thống sẽ tự động khôi phục và chiếm lại 1 suất chỉ tiêu cho học sinh này tại ngành học tương ứng.
    ![Hình 5.5: Giao diện sửa và khôi phục trạng thái hồ sơ học sinh](images/admin_student_restore.png)

---

## 5. Thay đổi thời hạn giữ học sinh cho CTV (Lead Lock-Time)
Mặc định khi học sinh đăng ký qua link của CTV A nhưng chưa đóng tiền, hồ sơ này sẽ được giữ riêng cho CTV A chăm sóc trong vòng **14 ngày**. Sau 14 ngày, nếu học sinh chưa đóng tiền và CTV B chốt được, hệ thống sẽ tự chuyển học sinh đó sang cho CTV B khi họ nộp tiền.

Nếu bạn muốn tăng/giảm thời hạn khóa giữ lead này (Ví dụ: tăng lên 30 ngày hoặc giảm xuống 7 ngày):
1.  Liên hệ quản trị kỹ thuật mở file cài đặt cấu hình môi trường `.env`.
2.  Thay đổi dòng cấu hình:
    ```env
    LEAD_LOCK_DAYS=14
    ```
    *(Thay đổi số 14 thành số ngày mong muốn).*
    ![Hình 5.6: Cài đặt biến số ngày khóa giữ lead trong file cấu hình .env](images/admin_lead_lock_config.png)
3.  Chạy lệnh xóa cache để áp dụng cài đặt mới:
    ```bash
    php artisan config:clear
    ```
