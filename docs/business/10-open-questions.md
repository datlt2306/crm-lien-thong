# 10-open-questions.md - Giả định, Câu hỏi mở & Ghi chú kỹ thuật

## 1. Các giả định nghiệp vụ chính (Key Business Assumptions)
* **Kênh truyền thông Telegram:** Giả định rằng toàn bộ CTV chính (Master CTV) đều hoạt động tích cực trên Telegram và đã thực hiện kết nối tài khoản bằng lệnh `/start` gửi đến bot để lấy Chat ID cập nhật vào database. Nếu CTV không cập nhật Chat ID, hệ thống sẽ bỏ qua bước gửi thông báo biến động số dư và báo cáo tự động qua bot.
* **Thời gian đối soát (Mùng 5 hàng tháng):** Giả định ngày mùng 5 hàng tháng là mốc cứng để quyết toán hoa hồng cho các hồ sơ Chính quy. Hệ thống hiện chỉ đánh dấu `payable` tự động dựa trên thời gian tạo chứ chưa có cron job quét tự động để khóa/chốt định kỳ cấp hệ thống, mà dựa vào thao tác chốt xuất Excel thủ công của kế toán.
* **Đơn vị tiền tệ:** Hệ thống chỉ hỗ trợ thanh toán nội địa bằng Việt Nam Đồng (VND). Mọi phép tính hoa hồng hoặc số dư ví được xử lý làm tròn không có phần thập phân thực tế.

### Evidence:
* Lọc Chat ID gửi tin nhắn Telegram: [TelegramBotService.php:L282-290](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/TelegramBotService.php#L282-L290)
* Nhãn quyết toán hiển thị qua bot: [TelegramBotService.php:L275-277](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/TelegramBotService.php#L275-L277)

---

## 2. Ghi chú về Mã nguồn cũ (Legacy Code Leftovers)
* **Phế bỏ cơ chế CTV đa cấp (Downline Commission Configs):**
  * Trong mã nguồn hiện tại, hệ thống đã loại bỏ hoàn toàn mô hình CTV cấp 2 (CTV giới thiệu CTV khác). Chỉ còn duy nhất 1 cấp CTV trực tiếp (`direct`).
  * Tuy nhiên, trong cơ sở dữ liệu vẫn còn tồn tại các migration liên quan đến `downline_commission_configs`, `upline_id` và các cột liên kết cấp độ.
  * Các phương thức như `upline()`, `downlines()`, `isLevel1()`, `isLevel2()` đã được dọn dẹp sạch khỏi Model `Collaborator`.
* **Loại bỏ cấu trúc Tổ chức (Organization Structure):**
  * Hệ thống ban đầu hỗ trợ phân quyền theo đơn vị liên kết (`organizations`). Hiện tại cấu trúc này đã được phế bỏ hoàn toàn để quy về quản trị trực tiếp cấp trường.
  * Migration `2026_04_17_183555_remove_organization_structure_completely.php` đã xóa bỏ các bảng liên kết tổ chức nhưng một số biến hoặc comment trong codebase vẫn còn đề cập đến thuật ngữ "Chủ đơn vị" (Owner).

### Evidence:
* Xóa quan hệ đa cấp trong Collaborator: [Collaborator.php:L53-55](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/Collaborator.php#L53-L55)
* Dọn dẹp tổ chức: Migration `2026_04_17_183555_remove_organization_structure_completely.php`

---

## 3. Các câu hỏi mở cần làm rõ (Open Questions)
1. **Quy trình duyệt chi tiền tự động:** Có cần tích hợp cổng thanh toán hoặc API chuyển khoản ngân hàng (ví dụ: VietQR/Napas) để tự động chi tiền từ Ví CTV khi họ yêu cầu rút, hay vẫn giữ nguyên cơ chế kế toán chuyển khoản tay rồi upload bill lên hệ thống?
2. **Xử lý số dư ví khi hoàn trả tiền:** Khi sinh viên rút hồ sơ và Payment chuyển sang `reverted`, hoa hồng chưa chi trả bị hủy (`cancelled`). Nhưng nếu hoa hồng tương ứng đã được quyết toán và chuyển vào ví khả dụng của CTV, hệ thống có tự động khấu trừ số dư ví CTV xuống âm (-) để cấn trừ vào đợt sau hay không?
3. **Cơ chế khóa chỉ tiêu năm:** Khi chỉ tiêu năm (`AnnualQuota`) đạt trạng thái `full`, hệ thống có nên tự động đóng tất cả các đợt tuyển sinh con (`Intake`) thuộc năm đó ngay lập tức hay vẫn cho phép admin phê duyệt thủ công?
