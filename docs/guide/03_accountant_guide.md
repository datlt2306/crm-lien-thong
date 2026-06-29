# Bài 3: Hướng dẫn dành cho Bộ phận Kế toán trường

Tài liệu này hướng dẫn kế toán thực hiện các công việc kiểm tra học phí, duyệt yêu cầu rút tiền của Cộng tác viên (CTV) và thực hiện các điều chỉnh tiền hoa hồng thủ công.

---

## 1. Duyệt học phí / Lệ phí tuyển sinh của học sinh
Khi học sinh tải ảnh biên lai chuyển khoản lên, thông tin sẽ xuất hiện trong danh sách phiếu thu chờ duyệt.

1.  Đăng nhập vào trang quản trị, chọn mục **Phiếu thu (Payments)**.
    ![Hình 3.1: Danh sách các phiếu thu đóng lệ phí chờ duyệt trên trang quản trị](images/accountant_payment_list.png)
2.  Mở chi tiết phiếu thu, xem ảnh biên lai chuyển tiền đi kèm và kiểm tra tài khoản ngân hàng của trường xem tiền đã nổi chưa.
3.  Nếu thông tin số tiền và người nộp khớp với tài khoản ngân hàng:
    *   Chuyển trạng thái phiếu thu sang **Đã xác thực (Verified)**.
    *   Hệ thống sẽ tự động trừ 1 chỉ tiêu đăng ký tuyển sinh của ngành học đó.
    *   Hệ thống tự động tính hoa hồng cho CTV giới thiệu dựa theo chính sách quy định (Kế toán không cần tính thủ công).
    ![Hình 3.2: Giao diện xem biên lai và nhấn Xác thực học phí học sinh](images/accountant_verify_payment.png)

---

## 2. Phê duyệt và Thanh toán tiền rút cho CTV
Khi CTV gửi yêu cầu rút tiền hoa hồng tích lũy về tài khoản ngân hàng:

1.  Vào mục **Yêu cầu rút tiền (Withdrawals / Wallet Transactions)**.
    ![Hình 3.3: Bảng danh sách các yêu cầu rút tiền chờ xử lý của CTV](images/accountant_withdrawal_list.png)
2.  Lọc danh sách các yêu cầu có trạng thái "Đang chờ" (`pending`).
3.  Xem thông tin tài khoản ngân hàng thụ hưởng của CTV được liệt kê trong chi tiết yêu cầu (Họ tên chủ thẻ, Số tài khoản ngân hàng, Tên ngân hàng).
4.  Kế toán thực hiện chuyển tiền ngoài (qua App ngân hàng của trường) cho CTV đúng số tiền họ yêu cầu.
5.  Sau khi chuyển tiền thành công, bấm nút **Xác nhận thanh toán (Approve)** trên hệ thống để trừ số dư ví của CTV và ghi nhận lệnh rút đã hoàn tất.
    ![Hình 3.4: Nút duyệt lệnh rút tiền và cập nhật trạng thái thanh toán](images/accountant_approve_withdrawal.png)

---

## 3. Khấu trừ hoa hồng thủ công khi học sinh rút hồ sơ hoàn phí
Nếu học sinh đã được duyệt nộp tiền nhưng sau đó xin rút hồ sơ, nhà trường hoàn trả tiền học phí và cần thu hồi tiền hoa hồng đã chuyển vào ví CTV:

*   *Lưu ý: Để đảm bảo an toàn, hệ thống không tự động trừ tiền ví của CTV khi học sinh rút hồ sơ. Kế toán phải thực hiện việc này bằng tay.*

1.  Tìm hồ sơ **Hoa hồng (Commissions)** liên quan đến học sinh rút hồ sơ đó.
2.  Cuộn xuống phần **Điều chỉnh hoa hồng (Commission Adjustments)** và bấm nút **Tạo mới (Create)**.
3.  Nhập thông tin điều chỉnh:
    *   **CTV thụ hưởng**: Chọn tên CTV giới thiệu (Ví dụ: Lê Trọng Đạt).
    *   **Số tiền điều chỉnh**: Nhập số tiền **âm** tương đương với số tiền cần thu hồi (Ví dụ: `-1,750,000` đối với hệ chính quy, hoặc `-750,000` đối với hệ vừa học vừa làm).
    *   **Lý do điều chỉnh**: Ghi rõ nguyên nhân (Ví dụ: *Thu hồi tiền giới thiệu do học sinh Kim Hồng Phong rút hồ sơ rút tiền*).
    *   **Trạng thái**: Chọn `received_confirmed`.
4.  Bấm Lưu. Hệ thống sẽ tự động trừ trực tiếp số tiền này vào số dư ví tích lũy của CTV.
    ![Hình 3.5: Giao diện Form thêm dòng điều chỉnh trừ tiền hoa hồng thủ công](images/accountant_manual_adjustment.png)
