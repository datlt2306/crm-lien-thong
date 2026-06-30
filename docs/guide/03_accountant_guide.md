# Bài 3: Hướng dẫn dành cho Bộ phận Kế toán trường

Tài liệu này hướng dẫn kế toán thực hiện các công việc kiểm tra học phí, chi trả tiền hoa hồng cho Cộng tác viên (CTV) và thực hiện các điều chỉnh tiền hoa hồng thủ công.

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

## 2. Thanh toán tiền hoa hồng cho CTV
Hệ thống không quản lý ví tiền hay yêu cầu rút phức tạp. Kế toán sẽ đối soát trực tiếp trên danh sách dòng hoa hồng phát sinh:

1.  Vào mục **Dòng hoa hồng (Commission Items)**.
    ![Hình 3.3: Bảng danh sách toàn bộ các dòng hoa hồng của CTV trên hệ thống](images/accountant_withdrawal_list.png)
2.  Lọc danh sách các dòng hoa hồng có trạng thái **Có thể thanh toán (Payable)**.
3.  Xem thông tin tài khoản ngân hàng thụ hưởng của CTV thụ hưởng dòng hoa hồng đó (được hiển thị chi tiết trong dòng hoa hồng).
4.  Kế toán thực hiện chuyển tiền ngoài (qua App ngân hàng của trường) cho CTV đúng số tiền hiển thị.
5.  Sau khi chuyển khoản thành công:
    *   Cập nhật trạng thái dòng hoa hồng sang **Đã thanh toán (Paid)** hoặc **Đã chốt & Đã chi (Payment Confirmed)**.
    *   Tải ảnh biên lai giao dịch chuyển khoản thành công của ngân hàng lên dòng hoa hồng đó làm căn cứ đối soát.
    *   Hệ thống sẽ tự động gửi tin nhắn Telegram báo cho CTV biết họ đã được nhận tiền kèm ảnh biên lai chuyển khoản.
    ![Hình 3.4: Giao diện cập nhật trạng thái thanh toán hoa hồng và đính kèm bill chuyển khoản ngân hàng](images/accountant_approve_withdrawal.png)

---

## 3. Khấu trừ hoa hồng thủ công khi học sinh rút hồ sơ hoàn phí
Nếu học sinh đã được duyệt nộp tiền nhưng sau đó xin rút hồ sơ, nhà trường hoàn trả tiền học phí và cần thu hồi tiền hoa hồng đã chi cho CTV:

1.  Tìm hồ sơ **Hoa hồng (Commissions)** liên quan đến học sinh rút hồ sơ đó.
2.  Cuộn xuống phần **Điều chỉnh hoa hồng (Commission Adjustments)** và bấm nút **Tạo mới (Create)**.
3.  Nhập thông tin điều chỉnh:
    *   **CTV thụ hưởng**: Chọn tên CTV giới thiệu (Ví dụ: Lê Trọng Đạt).
    *   **Số tiền điều chỉnh**: Nhập số tiền **âm** tương đương với số tiền cần thu hồi (Ví dụ: `-1,750,000` đối với hệ chính quy, hoặc `-750,000` đối với hệ vừa học vừa làm).
    *   **Lý do điều chỉnh**: Ghi rõ nguyên nhân (Ví dụ: *Thu hồi tiền giới thiệu do học sinh Kim Hồng Phong rút hồ sơ rút tiền*).
    *   **Trạng thái**: Chọn `received_confirmed`.
4.  Bấm Lưu. Hệ thống sẽ ghi nhận dòng điều chỉnh giảm trực tiếp vào lịch sử đối soát hoa hồng của CTV này.
    ![Hình 3.5: Giao diện Form thêm dòng điều chỉnh trừ tiền hoa hồng thủ công](images/accountant_manual_adjustment.png)
