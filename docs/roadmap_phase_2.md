# Lộ trình Phát triển Hệ thống - Phase 2: Nâng cấp lên CRM Toàn diện

Tài liệu này ghi lại các định hướng tính năng và giải pháp kỹ thuật dự kiến sẽ triển khai trong Phase 2, nhằm nâng cấp cổng tuyển sinh hiện tại thành một hệ thống quản lý quan hệ khách hàng (CRM) chuyên nghiệp cho trường GTVT.

---

## 1. Module 1: Nhật ký Chăm sóc & Tương tác (Interaction Timeline)
*   **Mục tiêu**: Giúp cán bộ tuyển sinh lưu lại lịch sử chi tiết của từng cuộc gọi, email tư vấn với học sinh.
*   **Giải pháp kỹ thuật (Laravel + Filament)**:
    *   Tạo bảng `student_interactions` lưu thông tin: loại tương tác (`call`, `email`, `note`, `sms`), cán bộ thực hiện, nội dung chi tiết.
    *   Tích hợp tab **Nhật ký chăm sóc (Timeline)** trong trang chi tiết học viên sử dụng Filament Relation Manager.
    *   Thêm tính năng nhập nhanh các kết quả cuộc gọi (Ví dụ: *Học sinh thuê bao, Học sinh hẹn gọi lại sau, Đã hướng dẫn nộp hồ sơ...*).

---

## 2. Module 2: Phễu Tuyển sinh trực quan (Kanban Pipeline)
*   **Mục tiêu**: Quản lý trạng thái học sinh bằng bảng kéo thả trực quan để dễ dàng đánh giá tỷ lệ chuyển đổi.
*   **Giải pháp kỹ thuật (Laravel + Filament)**:
    *   Sử dụng gói thư viện mã nguồn mở **`filament-kanban`**.
    *   Ánh xạ các cột của bảng Kanban trực tiếp với cột trạng thái `status` của bảng sinh viên (`new` $\rightarrow$ `contacted` $\rightarrow$ `submitted` $\rightarrow$ `approved` $\rightarrow$ `enrolled`).
    *   Khi cán bộ kéo thả thẻ học sinh giữa các cột, hệ thống tự động gọi API cập nhật trạng thái trong cơ sở dữ liệu ngầm.

---

## 3. Module 3: Tích hợp Tin nhắn & Cuộc gọi (Multi-channel Integration)
*   **Mục tiêu**: Gọi điện và nhắn tin cho học sinh ngay từ giao diện CRM mà không cần thao tác thủ công bên ngoài.
*   **Giải pháp kỹ thuật**:
    *   *Tích hợp Tổng đài ảo (VoIP)*: Kết nối các cổng tổng đài VoIP tại Việt Nam (như Stringee, Omicall) thông qua WebRTC SDK để cán bộ click là gọi trực tiếp từ trình duyệt. Tự động ghi âm cuộc gọi và lưu link ghi âm vào nhật ký chăm sóc.
    *   *Tích hợp Zalo ZNS (Zalo Notification Service)*: Gửi tin nhắn chăm sóc tự động theo mẫu đã duyệt của Zalo khi hồ sơ thay đổi trạng thái.
    *   *Tích hợp SMS Brandname*: Gửi tin nhắn SMS nhắc đóng phí hoặc thông báo kết quả xét tuyển.

---

## 4. Module 4: Quản lý Lịch hẹn & Nhiệm vụ tự động (Task & Follow-up)
*   **Mục tiêu**: Nhắc nhở cán bộ gọi lại cho học sinh đúng giờ hẹn, tự động giao việc chăm sóc.
*   **Giải pháp kỹ thuật**:
    *   Tạo bảng `tasks` lưu lịch hẹn, ngày đến hạn (`due_at`), cán bộ phụ trách và trạng thái nhiệm vụ.
    *   Tạo Widget *"Nhiệm vụ cần xử lý trong ngày"* trên trang chủ Admin của từng cán bộ.
    *   Hệ thống tự động nhắc nhở (qua Telegram Bot hoặc thông báo trên web) khi đến giờ hẹn gọi lại cho học sinh.

---

## 5. Module 5: Báo cáo Phân tích Phễu & Hiệu suất CTV (Sales Analytics)
*   **Mục tiêu**: Đo lường chính xác hiệu quả tuyển sinh của từng đợt, hiệu suất của CTV và tỷ lệ chuyển đổi của các nguồn quảng cáo.
*   **Giải pháp kỹ thuật**:
    *   Xây dựng báo cáo biểu đồ phễu chuyển đổi tuyển sinh (Conversion Funnel Chart).
    *   Bảng xếp hạng hiệu suất CTV (CTV giới thiệu được nhiều học sinh nhất, CTV có tỷ lệ học sinh đóng phí cao nhất).
    *   Thống kê doanh thu học phí thực tế thu được theo từng tháng/quý/năm.
