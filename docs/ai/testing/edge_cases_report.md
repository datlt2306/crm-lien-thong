# Báo cáo Luồng Nghiệp vụ Ngoại lệ (Edge Cases) sau Đăng ký

Tài liệu này ghi lại chi tiết luồng xử lý và kết quả đối soát dữ liệu cho các trường hợp ngoại lệ sau khi sinh viên đã đăng ký thành công trên hệ thống.

---

## 1. Trường hợp 1: Chuyển đổi hệ đào tạo / ngành học (Student Transfer)
Khi sinh viên thay đổi chương trình học (ví dụ: chuyển từ hệ Chính quy sang VHVL hoặc Đào tạo từ xa), hệ thống sẽ tính toán lại học phí và hoa hồng của Cộng tác viên.

### Mô tả luồng chuyển hệ:
1.  **Giải phóng chỉ tiêu cũ & Chiếm chỉ tiêu mới**: 
    *   Hệ thống trừ đi 1 chỉ tiêu thực tế (`current_quota`) ở hệ cũ và cộng thêm 1 chỉ tiêu thực tế ở hệ mới thông qua hàm `handleStudentTransfer` trong `QuotaService`.
2.  **Ghi nhận chênh lệch học phí**:
    *   Tạo bản ghi `StudentTransfer` ghi lại lịch sử chuyển hệ.
    *   Tạo bản ghi `PaymentAdjustment` đại diện cho phần học phí thừa/thiếu. Không sửa trực tiếp vào Phiếu thu ban đầu.
3.  **Khấu trừ/Tính lại hoa hồng**:
    *   So sánh chính sách hoa hồng của hệ mới với số tiền hoa hồng thực tế CTV đã nhận.
    *   Tạo bản ghi `CommissionAdjustment` ghi nhận số tiền chênh lệch.
    *   Nếu chênh lệch âm (Ví dụ: CQ được 1.75tr, chuyển sang VHVL đợt đầu chỉ được nhận 750k $\rightarrow$ chênh lệch `-1,000,000đ`), hệ thống thực hiện khấu trừ trực tiếp vào ví của CTV.

---

## 2. Trường hợp 2: Học viên rút hồ sơ & Hoàn học phí (Dropout & Refund)
Áp dụng khi học viên rút hồ sơ sau khi đã nộp tiền và kế toán tiến hành hoàn trả học phí.

### Mô tả luồng xử lý:
1.  **Giải phóng chỉ tiêu (Quota)**:
    *   Trạng thái của sinh viên được cập nhật thành `dropped` (Bỏ học) hoặc `rejected` (Từ chối).
    *   Hệ thống tự động giải phóng 1 chỉ tiêu thực tế (`current_quota` giảm đi 1).
2.  **Đổi trạng thái Phiếu thu**:
    *   Phiếu thu học phí chuyển sang trạng thái `reverted` (Hoàn trả).
3.  **Thu hồi hoa hồng đã trả cho CTV**:
    *   *Vì tính an toàn kế toán, hệ thống không tự động trừ tiền ví của CTV khi phiếu thu bị hoàn trả.* 
    *   Kế toán sẽ kiểm tra thủ công và tạo một bản ghi điều chỉnh âm (`CommissionAdjustment`) tương đương với số tiền hoa hồng đã chi (Ví dụ: `-1,750,000đ`).
    *   Thực hiện giao dịch rút tiền ví CTV (`withdrawal`) qua `addCommissionToWallet` để đưa số dư ví khả dụng của CTV về đúng trạng thái ban đầu.

---

## 3. Trường hợp 3: Khóa biên chỉ tiêu (Quota Overflow)
Khi số lượng học sinh đăng ký và nộp hồ sơ vượt quá giới hạn tối đa (`target_quota`) của đợt tuyển sinh.

### Cơ chế ngăn chặn tràn chỉ tiêu:
1.  **Kiểm tra số lượng slot trống**:
    *   Công thức: `available_slots = target_quota - (current_quota + pending_quota)`.
2.  **Chặn ghi danh**:
    *   Khi có sinh viên mới đăng ký qua link ref, nếu `available_slots <= 0`, hệ thống sẽ trả về lỗi Validation và chặn đăng ký, chuyển trạng thái chỉ tiêu sang Đã đầy (`full`).
3.  **Chống Race Condition**:
    *   Hệ thống áp dụng `lockForUpdate()` trên hàng dữ liệu của Quota được chọn trong cơ sở dữ liệu trước khi kiểm tra số slot trống, đảm bảo tính tuần tự khi có nhiều học sinh đăng ký cùng lúc ở giây cuối cùng.

---

## 4. Trường hợp 4: Khóa giữ Lead của Cộng tác viên (Lead Lock-Time)
Giải quyết trường hợp sinh viên đã đăng ký qua link của CTV A nhưng chưa nộp tiền. CTV B sau đó tiếp cận, chăm sóc và thuyết phục được học sinh nộp tiền qua link của mình.

### Cơ chế bảo vệ và chuyển giao Lead:
1.  **Thời hạn khóa Lead (Mặc định 14 ngày)**:
    *   Hệ thống cấu hình tham số `lead_lock_days` trong `config/services.php` (mặc định là `14` ngày).
2.  **Trong vòng 14 ngày**:
    *   Hồ sơ được khóa chặt cho CTV A. Nếu học viên cố tình nộp tiền qua link của CTV B, hệ thống sẽ chặn lại và báo lỗi: *"Học viên này đang được khóa để CTV khác chăm sóc..."*
3.  **Sau 14 ngày (Hết hạn khóa)**:
    *   Nếu học viên vẫn chưa được duyệt đóng tiền (`status` chưa phải `verified`), và họ quyết định nộp tiền qua link của CTV B, hệ thống sẽ **tự động chuyển giao** học viên sang cho CTV B (`collaborator_id` cập nhật sang CTV B và người hướng dẫn cập nhật sang tên CTV B). Hoa hồng sau đó sẽ được tính toàn bộ cho CTV B.
4.  **Khi đã duyệt học phí**:
    *   Một khi học viên đã đóng học phí và được kế toán duyệt (`verified`), người giới thiệu bị khóa vĩnh viễn và không thể thay đổi thông qua link nộp tiền của CTV khác nữa.

---

## 5. Trường hợp 5: Khôi phục hồ sơ tuyển sinh (Restore Student Profile)
Sinh viên đã rút hồ sơ hoặc bị từ chối (`dropped`/`rejected`) nhưng sau đó xin quay trở lại học tập.

### Cơ chế tái chiếm chỉ tiêu (Quota Re-occupation):
1.  **Sự kiện khôi phục**:
    *   Khi Admin thay đổi trạng thái của học viên từ `dropped`/`rejected` sang các trạng thái hoạt động khác.
2.  **Tự động tính toán lại Quota**:
    *   Hệ thống gọi hàm `handleStudentRestoration` trong `QuotaService`.
    *   Nếu học sinh **đã nộp tiền trước đó** và phiếu thu đang ở trạng thái `verified`, hệ thống tự động tăng lại chỉ tiêu thực tế (`current_quota` tăng 1) và cộng lại chỉ tiêu năm `AnnualQuota`.
    *   Nếu học sinh **chưa đóng tiền**, hệ thống tự động cộng lại chỉ tiêu chờ duyệt (`pending_quota` tăng 1).

