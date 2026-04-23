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
-   CTV có tài khoản hệ thống được đồng bộ mật khẩu và email chặt chẽ với bản ghi Collaborator.
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
    -   Theo dõi toàn bộ pipeline của sinh viên mình phụ trách.
    -   Biết rõ **khi nào được nhận hoa hồng**, số tiền dự kiến và số tiền đã chi trả.
-   Hệ thống đảm bảo:
    -   CTV chỉ nhìn thấy dữ liệu **cần thiết và phù hợp** (không lộ dữ liệu nhạy cảm không liên quan).
    -   Mọi tính toán hoa hồng, ghi nhận thanh toán được xử lý tập trung, minh bạch.

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

### 3.2. Xem danh sách và chi tiết sinh viên trong nhánh của CTV

-   **Mô tả**:
    -   CTV xem tất cả sinh viên:
        -   Do chính mình phụ trách.
        -   Thuộc nhánh downline của mình.
-   **Căn cứ từ code**:
    -   `StudentResource::getEloquentQuery()`:
        -   Nếu user có role `ctv`, hệ thống:
            -   Tìm `Collaborator` theo email của user.
            -   Lấy danh sách ID downline (qua hàm `getDownlineIds`).
            -   Truy vấn `Student` với `collaborator_id` nằm trong danh sách `[ctv_id + downline_ids]`.
-   **Yêu cầu hệ thống**:
    -   API trả về danh sách sinh viên:
        -   Bị giới hạn theo nhánh CTV (không xem được sinh viên của nhánh khác).
        -   Có thể filter theo trạng thái hồ sơ, trạng thái thanh toán, đợt tuyển sinh, ngành, v.v.
    -   API chi tiết:
        -   CTV có thể xem và chỉnh sửa **Thông tin cơ bản** (Họ tên, SĐT, Email, Ngành đăng ký) của sinh viên mình phụ trách nếu hồ sơ chưa được xác minh.
        -   CTV **không được phép** xem các tab thông tin nhạy cảm khác như: Thông tin THPT, Thông tin CĐ/TC, và Checklist giấy tờ minh chứng (quyền này thuộc về Cán bộ hồ sơ).
        -   Không được xem các ghi chú nội bộ của Cán bộ hồ sơ/Kế toán.

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
    -   Xác nhận chính thức, upload phiếu thu chính thức, đối soát chi tiết:
        -   Thuộc quyền của **Kế toán / Cán bộ hồ sơ** theo `PaymentPolicy`.
    -   **Chỉnh sửa thông tin thanh toán**:
        -   CTV có thể chỉnh sửa lại bill hoặc số tiền đã upload nếu phát hiện sai sót (trước khi Kế toán xác nhận).
        -   **Bắt buộc** phải nhập lý do chỉnh sửa khi thực hiện thao tác này.
    -   Quy tắc đặt tên file:
        -   File bill được hệ thống tự động đặt tên theo chuẩn: `{Mã_HS}_{Tên}_{Ngành}_{Hệ}.ext` để dễ quản lý trên Drive.

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
    -   CTV **xác nhận đã nhận hoa hồng**:
        -   Khi trạng thái hoa hồng chuyển sang `payment_confirmed` (Admin/Kế toán đã chi), CTV có trách nhiệm kiểm tra tài khoản và nhấn xác nhận `received_confirmed` (Đã nhận tiền).
        -   Thao tác này giúp hệ thống chốt giao dịch và ghi nhận vào lịch sử ví.
    -   CTV **không được**:
        -   Tự chỉnh sửa số liệu hoa hồng.
        -   Tạo giao dịch ví thủ công; các giao dịch phải được tạo bởi hệ thống hoặc bởi Kế toán/Quản lý theo rule.

### 3.6. Quản lý downline (nếu áp dụng mô hình nhiều tầng)

-   **Mô tả**:
    -   CTV có thể có các **downline** (CTV tuyến dưới) trong nhánh của mình; hưởng hoa hồng từ sinh viên của downline theo chính sách.
-   **Căn cứ từ code**:
    -   `Collaborator` có trường `upline_id`.
    -   `StudentResource::getDownlineIds` thực hiện đệ quy để lấy toàn bộ cây downline.
-   **Yêu cầu hệ thống**:
    -   API cho phép CTV:
        -   Xem danh sách downline (nếu được phân quyền).
        -   Xem số lượng sinh viên và doanh thu/hoa hồng phát sinh từ mỗi downline (tổng hợp).
    -   Rule phân chia hoa hồng multi-level:
        -   Do backend (CommissionPolicy / CommissionService) quyết định, phía CTV chỉ xem được kết quả.

## 4. Quy tắc quyền hạn & bảo mật cho Cộng tác viên

-   CTV **chỉ được truy cập**:
    -   Sinh viên do mình phụ trách (`collaborator_id` = CTV hiện tại) hoặc thuộc nhánh downline.
    -   Payment, Commission, Wallet liên quan tới mình hoặc nhánh của mình.
    -   Truy cập file (bill/phiếu thu) thông qua URL bảo mật có token để tránh IDOR.
-   CTV **không được**:
    -   Xem danh sách tổ chức (Organization) đầy đủ hoặc chỉnh sửa thông tin tổ chức.
    -   Sửa trực tiếp dữ liệu thanh toán đã được xác nhận bởi Kế toán.
    -   Thay đổi cấu hình chính sách hoa hồng, quota, ngành/đợt.
-   Mọi API cần:
    -   Kiểm tra mapping giữa tài khoản đăng nhập (User) và `Collaborator`.
    -   **Đồng bộ tài khoản**: Mọi thay đổi về Email hoặc Mật khẩu trên bản ghi CTV phải được đồng bộ ngay lập tức sang tài khoản User tương ứng để đảm bảo tính nhất quán trong đăng nhập.
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

## 7. Trạng thái các vấn đề mở (Open Items)
-   **Đã giải quyết**:
    -   Quyền xem sinh viên giới hạn trong nhánh và downline (theo logic `getDownlineIds`).
    -   Đồng bộ tài khoản User và Collaborator (Email, Password).
    -   Quyền chỉnh sửa thông tin bill trước khi Kế toán xác nhận (kèm lý do).
-   **Cần làm rõ thêm**:
    -   Tỷ lệ chia sẻ hoa hồng cụ thể cho từng cấp bậc CTV trong mô hình đa tầng.
    -   Phạm vi báo cáo thống kê chi tiết cho từng CTV.
