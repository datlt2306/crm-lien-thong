---
phase: requirements
role: cong_tac_vien
title: SRS – Vai trò Cộng tác viên
description: Đặc tả yêu cầu nghiệp vụ và hành vi hệ thống cho vai trò Cộng tác viên trong CRM Tuyển sinh Liên thông
---

## 1. Giới thiệu & Bối cảnh

### 1.1. Mô tả vai trò

-   **Cộng tác viên (CTV)** là người:
    -   Tìm kiếm và tư vấn sinh viên tiềm năng.
    -   Tạo hoặc hỗ trợ tạo **lead/hồ sơ sinh viên** trong hệ thống.
    -   Theo dõi tiến độ xử lý hồ sơ sinh viên thuộc nhánh của mình.
    -   Liên quan trực tiếp tới **hoa hồng**, **thanh toán** và **chăm sóc sinh viên**.
-   CTV có thể làm việc độc lập hoặc thuộc một tổ chức (Organization).

### 1.2. Phạm vi SRS cho Cộng tác viên

-   Mô tả các API / nghiệp vụ backend cho phép CTV:
    -   Tạo và quản lý danh sách sinh viên mình phụ trách (bao gồm downline).
    -   Theo dõi trạng thái hồ sơ, trạng thái thanh toán của sinh viên.
    -   Xem và kiểm tra hoa hồng, ví hoa hồng (wallet), lịch sử thanh toán hoa hồng.
-   Không bao gồm chi tiết UI, tập trung vào:
    -   Quyền truy cập dữ liệu.
    -   Luồng nghiệp vụ tạo lead, gán CTV, xem báo cáo cá nhân.

## 2. Mục tiêu theo vai trò

-   CTV có thể:
    -   Chủ động tạo lead sinh viên mới.
    -   Theo dõi toàn bộ pipeline của sinh viên trực tiếp do mình phụ trách.
    -   Biết rõ **khi nào được nhận hoa hồng**, số tiền dự kiến và số tiền đã chi trả.
-   Hệ thống đảm bảo:
    -   Tài khoản CTV được **quản lý tập trung**: CTV không thể tự do đăng ký qua form Public, mà chỉ được cấp mới từ những người có thẩm quyền (Super Admin hoặc Chủ Tổ Chức) từ bên trong Hệ thống.
    -   CTV chỉ nhìn thấy dữ liệu **cần thiết và phù hợp** (không lộ dữ liệu nhạy cảm không liên quan).
    -   Mọi tính toán hoa hồng, ghi nhận thanh toán được xử lý tập trung, minh bạch và **chỉ dựa trên mô hình hoa hồng trực tiếp (1 cấp)**.

## 3. Use Case chính cho Cộng tác viên

### 3.1. Tạo lead / hồ sơ sinh viên

-   **Mô tả**:
    -   CTV nhập thông tin sinh viên tiềm năng vào hệ thống để bắt đầu quy trình tuyển sinh.
-   **Yêu cầu hệ thống**:
    -   API cho phép:
        -   Tạo bản ghi `Student` ở trạng thái **lead** hoặc tương đương.
        -   Gắn `collaborator_id` tương ứng với CTV hiện tại.
    -   Dữ liệu tối thiểu:
        -   Họ tên, số điện thoại, thông tin liên lạc cơ bản.
    -   Sau khi tạo:
        -   Có thể gửi link cho sinh viên tự hoàn thiện hồ sơ hoặc CTV nhập giúp.

### 3.2. Xem danh sách và chi tiết sinh viên của CTV

-   **Mô tả**:
    -   CTV xem tất cả sinh viên do chính mình trực tiếp phụ trách.
    -   Hệ thống không còn áp dụng mô hình đa cấp (downline), CTV chỉ nhìn thấy Data của cá nhân.
-   **Căn cứ từ code**:
    -   `StudentResource::getEloquentQuery()`:
        -   Nếu user có role `ctv`, hệ thống chỉ truy vấn `Student` với `collaborator_id` bằng đúng ID của CTV hiện tại.
-   **Yêu cầu hệ thống**:
    -   API trả về danh sách sinh viên:
        -   Chỉ giới hạn ở các sinh viên do CTV trực tiếp giới thiệu.
        -   Có thể filter theo trạng thái hồ sơ, trạng thái thanh toán, đợt tuyển sinh, ngành, v.v.
    -   API chi tiết:
        -   CTV có thể xem chi tiết hồ sơ mức độ đủ dùng cho chăm sóc (thông tin liên hệ, trạng thái, ghi chú cơ bản).
        -   Không được xem các ghi chú nội bộ riêng của Cán bộ hồ sơ/Kế toán nếu được phân loại là nội bộ.

### 3.3. Theo dõi trạng thái hồ sơ sinh viên

-   **Mô tả**:
    -   CTV cần biết sinh viên đã hoàn thành hồ sơ tới đâu để chủ động nhắc nhở.
-   **Yêu cầu hệ thống**:
    -   API cung cấp cho CTV:
        -   Trạng thái hồ sơ: đang nhập, đã nộp, thiếu giấy tờ, đủ điều kiện, không đủ điều kiện, v.v.
        -   Trạng thái thanh toán: chưa nộp, đã nộp, đang chờ xác nhận, đã xác nhận, từ chối, v.v.
        -   Các “cờ” quan trọng: sắp hết hạn đợt, quota gần đầy (nếu có).
    -   CTV có thể đọc nhưng không được:
        -   Tự ý sửa trạng thái duyệt học thuật hoặc thông tin ngành/đợt sau khi nộp (đây là quyền của Cán bộ hồ sơ/Quản lý).

### 3.4. Hỗ trợ sinh viên nộp lệ phí / cập nhật thông tin thanh toán

-   **Mô tả**:
    -   CTV có thể hướng dẫn sinh viên nộp khoản phí đăng ký và xác nhận với hệ thống rằng sinh viên đã chuyển tiền.
-   **Căn cứ từ `PaymentPolicy`**:
    -   CTV có quyền:
        -   `viewAny` và `view` một số `Payment` liên quan tới sinh viên mình:
            -   Quy tắc xem chi tiết payment cho role `ctv` dựa trên quan hệ `primary_collaborator_id` hoặc `sub_collaborator_id`.
-   **Yêu cầu hệ thống**:
    -   API cho phép CTV:
        -   Xem danh sách và chi tiết `Payment` liên quan tới sinh viên của mình.
        -   Cập nhật một số thông tin xác nhận ban đầu (ví dụ: đã thu hộ, đã nhờ sinh viên chuyển khoản), nếu thiết kế nghiệp vụ cho phép.
    -   Xác nhận chính thức, upload phiếu thu, đối soát chi tiết:
        -   Thuộc quyền của **Kế toán / Cán bộ hồ sơ** theo `PaymentPolicy`.

### 3.5. Theo dõi hoa hồng, ví hoa hồng và giao dịch

-   **Mô tả**:
    -   CTV cần xem:
        -   Tổng hoa hồng đã phát sinh, đã chi trả, còn lại.
        -   Chi tiết từng khoản hoa hồng gắn với sinh viên/hồ sơ cụ thể.
-   **Liên quan đến model**:
    -   `Commission`, `CommissionItem`, `Wallet`, `WalletTransaction`, `Collaborator`.
-   **Yêu cầu hệ thống**:
    -   API cho phép CTV:
        -   Xem số dư ví hoa hồng (`Wallet` gắn với CTV).
        -   Xem lịch sử giao dịch (`WalletTransaction`): ngày, loại giao dịch (cộng/trừ), số tiền, lý do.
        -   Xem bảng hoa hồng chi tiết theo hồ sơ sinh viên (`Commission` / `CommissionItem`):
            -   Sinh viên nào, trạng thái hồ sơ/thu tiền, số tiền hoa hồng tương ứng.
    -   CTV **không được**:
        -   Tự chỉnh sửa số liệu hoa hồng.
        -   Tạo giao dịch ví thủ công; các giao dịch phải được tạo bởi hệ thống hoặc bởi Kế toán/Quản lý theo rule.
    -   **Bảo mật Ví (Wallet Security)**:
        -   Hệ thống nghiêm cấm và từ chối xử lý tất cả các yêu cầu Nạp (Deposit), Rút (Withdraw) hoặc Chuyển tiền (Transfer) có giá trị `< 0` hoặc `= 0`.
        -   Không cho phép thực hiện thao tác chuyển tiền tới chính Ví của người gửi (Loop Transaction).
        -   Xảy ra trong môi trường khoá DB (`DB::transaction` & `lockForUpdate`) nhằm tránh các lỗi Race Condition tiềm tàng dẩn đến thất thoát quỹ.



## 4. Quy tắc quyền hạn & bảo mật cho Cộng tác viên

-   CTV **chỉ được truy cập**:
    -   Sinh viên do chính mình trực tiếp phụ trách (`collaborator_id` = CTV hiện tại).
    -   Payment, Commission, Wallet liên quan trực tiếp tới mình.
-   CTV **không được**:
    -   Xem danh sách tổ chức (Organization) đầy đủ hoặc chỉnh sửa thông tin tổ chức.
    -   Sửa trực tiếp dữ liệu thanh toán đã được xác nhận bởi Kế toán.
    -   Thay đổi cấu hình chính sách hoa hồng, quota, ngành/đợt.
-   Mọi API cần:
    -   Kiểm tra mapping giữa tài khoản đăng nhập (User) và `Collaborator`.
    -   Đảm bảo query luôn filter theo nhánh cộng tác viên tương ứng (áp dụng logic tương tự `StudentResource::getEloquentQuery`).

## 5. Dữ liệu & tích hợp liên quan đến Cộng tác viên

-   **Dữ liệu chính**:
    -   `Collaborator`: thông tin CTV, quan hệ `upline_id`, `organization_id`.
    -   `Student`: gắn `collaborator_id` hoặc thông tin CTV phụ trách.
    -   `Payment`: có thể gắn `primary_collaborator_id`, `sub_collaborator_id`.
    -   `Commission`, `CommissionItem`: lưu thông tin hoa hồng của CTV với từng hồ sơ/thanh toán.
    -   `Wallet`, `WalletTransaction`: ví hoa hồng và lịch sử giao dịch.
-   **Tích hợp**:
    -   Thông báo (notification):
        -   Khi sinh viên trong nhánh nộp hồ sơ, nộp tiền, bị thiếu giấy tờ, được duyệt/không duyệt, v.v.
    -   Báo cáo xuất Excel:
        -   CTV không trực tiếp export báo cáo toàn hệ thống, nhưng có thể được cung cấp API/endpoint xem/tải báo cáo giới hạn cho nhánh của mình (tùy nhu cầu).

## 6. Tiêu chí thành công theo vai trò Cộng tác viên

-   CTV có thể:
    -   Tạo và quản lý lead/sinh viên mà **không cần** thao tác ngoài hệ thống (Excel riêng lẻ).
    -   Nắm được tiến độ hồ sơ của từng sinh viên để chăm sóc kịp thời.
    -   Biết rõ và tin tưởng số liệu hoa hồng, lịch sử chi trả trong hệ thống.
-   Hệ thống đảm bảo:
    -   Không lộ dữ liệu hồ sơ/thu nhập của nhánh khác cho CTV.
    -   Giảm thiểu tranh chấp về hoa hồng nhờ log đầy đủ và rule rõ ràng.

## 7. Open Items riêng cho vai trò Cộng tác viên

-   Quy tắc giới hạn/chặn thao tác khi:
    -   CTV cố gắng chỉnh sửa thông tin nhạy cảm sau khi hồ sơ đã ở trạng thái khoá.
