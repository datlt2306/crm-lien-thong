---
phase: requirements
role: ke_toan
title: SRS – Vai trò Kế toán
description: Đặc tả yêu cầu nghiệp vụ và hành vi hệ thống cho vai trò Kế toán trong CRM Tuyển sinh Liên thông
---

## 1. Giới thiệu & Bối cảnh

### 1.1. Mô tả vai trò

-   **Kế toán** là người:
    -   Chịu trách nhiệm **đối soát, xác nhận và ghi nhận thanh toán** liên quan đến hồ sơ tuyển sinh.
    -   Quản lý và chi trả **hoa hồng cho Cộng tác viên**.
    -   Phối hợp với Cán bộ hồ sơ và Quản lý tổ chức để đảm bảo số liệu tài chính chính xác, minh bạch.

### 1.2. Phạm vi SRS cho Kế toán

-   Mô tả các nghiệp vụ backend cho phép Kế toán:
    -   Xem và xử lý danh sách thanh toán (`Payment`).
    -   Xác nhận thanh toán, từ chối, hoàn trả nếu có.
    -   Upload phiếu thu, chứng từ đi kèm.
    -   Theo dõi, tính toán và chi trả hoa hồng cho CTV (thông qua Commission/Wallet).
    -   Xuất báo cáo tài chính/hoa hồng liên quan tới tuyển sinh.

## 2. Mục tiêu theo vai trò

-   Đảm bảo:
    -   Mọi khoản thu từ sinh viên được ghi nhận **chính xác** và **đủ chứng từ**.
    -   Mọi khoản chi hoa hồng CTV được:
        -   Tính toán theo đúng chính sách.
        -   Có log đầy đủ (thời gian, người duyệt, hình thức chi trả).
-   Tối thiểu hóa việc dùng Excel thủ công bên ngoài, thay vào đó:
    -   Thực hiện đối soát và tổng hợp ngay trong hệ thống CRM.

## 3. Use Case chính cho Kế toán

### 3.1. Xem danh sách thanh toán liên quan đến tuyển sinh

-   **Mô tả**:
    -   Kế toán cần xem danh sách tất cả các `Payment` trong hệ thống, đặc biệt:
        -   Các khoản sinh viên đã nộp lệ phí tuyển sinh.
        -   Các khoản cần xác nhận, các khoản đã xác nhận, hoặc bị từ chối.
-   **Căn cứ từ `PaymentPolicy`**:
    -   Role `accountant` có quyền:
        -   `viewAny` và `view` tất cả các `Payment` (theo rule: `hasRole(['super_admin','admin','accountant','organization_owner','document'])`).
-   **Yêu cầu hệ thống**:
    -   API danh sách thanh toán:
        -   Hỗ trợ filter theo trạng thái (draft, submitted, verified, rejected, v.v.), thời gian, đợt tuyển sinh, phương thức thanh toán, CTV, tổ chức.
        -   Hỗ trợ tìm kiếm theo mã hồ sơ, tên sinh viên, số tham chiếu giao dịch.

### 3.2. Xác nhận thanh toán (verify payment)

-   **Mô tả**:
    -   Sau khi sinh viên nộp tiền (qua CTV hoặc trực tiếp), Kế toán phải:
        -   Đối chiếu giao dịch thực tế.
        -   Xác nhận vào hệ thống.
-   **Căn cứ từ `PaymentPolicy`**:
    -   Hàm `verify` cho phép:
        -   User có quyền `verify_payment` hoặc có role `accountant`, `document` được xác nhận thanh toán.
-   **Yêu cầu hệ thống**:
    -   API xác nhận thanh toán:
        -   Chỉ áp dụng cho `Payment` ở các trạng thái hợp lệ (ví dụ: submitted).
        -   Cho phép Kế toán:
            -   Đặt trạng thái `verified` (hoặc tương đương).
            -   Ghi chú (ví dụ: mã giao dịch, kênh nhận tiền, ghi chú nội bộ).
    -   Nếu từ chối hoặc hoàn trả tiền (`revert`):
        -   **Bắt buộc** ghi rõ lý do (thiếu chứng từ, sai số tiền, yêu cầu hoàn phí, v.v.).
        -   Có thể trả về trạng thái `rejected` hoặc `reverted`, đẩy thông báo cho sinh viên/CTV/Cán bộ hồ sơ.
        -   Hệ thống tự động xóa thông tin số phiếu thu và đường dẫn phiếu thu khi thực hiện hoàn trả để đảm bảo tính nhất quán.

### 3.3. Upload và quản lý phiếu thu

-   **Mô tả**:
    -   Kế toán là người **upload phiếu thu** (hóa đơn, chứng từ) lên hệ thống làm bằng chứng cho mỗi khoản thanh toán.
-   **Căn cứ từ `PaymentPolicy`**:
    -   Hàm `uploadReceipt`:
        -   Chỉ cho phép user có role `accountant`.
-   **Yêu cầu hệ thống**:
    -   API upload phiếu thu:
        -   Gắn file phiếu thu với bản ghi `Payment`.
        -   Lưu metadata: số phiếu thu, ngày lập, người lập, link Google Drive (nếu lưu trên Drive).
    -   Kế toán có thể:
        -   Thay thế phiếu thu (với log rõ ràng).
        -   **Chỉnh sửa bill thanh toán**: Cho phép CTV/Kế toán chỉnh sửa thông tin bill (số tiền, hệ đào tạo) trước khi xác nhận, nhưng **bắt buộc** nhập lý do chỉnh sửa.
        -   Không được xóa hoàn toàn chứng từ đã gắn với giao dịch nếu hệ thống yêu cầu lưu trữ lâu dài (trừ khi có luồng hủy bỏ có kiểm soát).
    -   Quy trình tải lên phiếu thu:
        -   Chỉ khả dụng sau khi thanh toán đã được xác nhận (`verified`).
        -   File phiếu thu được đặt tên theo chuẩn: `{Mã_HS}_{Tên}_{Ngành}_{Hệ}.ext`.

### 3.4. Quản lý và Chi trả Hoa hồng (Commission)
-   **Mô tả**: Quản lý việc chi trả hoa hồng cho Cộng tác viên sau khi các khoản lệ phí đã được xác minh.
-   **Quy trình chi trả**:
    -   Hệ thống tự động sinh các dòng hoa hồng ở trạng thái:
        -   `pending`: Chờ nhập học (nếu quy tắc yêu cầu SV nhập học mới được trả).
        -   `payable`: Có thể thanh toán (nếu trả ngay sau khi nộp tiền - thường vào ngày mùng 5 hàng tháng).
    -   Kế toán thực hiện chi trả và cập nhật trạng thái:
        -   `paid`: Đã thanh toán.
        -   `payment_confirmed`: Đã chốt & Đã chi (yêu cầu upload file minh chứng chi trả/bill chuyển khoản).
-   **Xác nhận từ CTV**:
    -   CTV sẽ nhận được thông báo và thực hiện xác nhận `received_confirmed` (Đã nhận tiền) trên portal của họ.
-   **Hủy hoa hồng**:
    -   Nếu Payment bị hoàn trả (`reverted`), các hoa hồng tương ứng sẽ chuyển sang trạng thái `cancelled` (Đã hủy).

### 3.5. Báo cáo tài chính & xuất số liệu

-   **Mô tả**:
    -   Kế toán cần trích xuất:
        -   Doanh thu (phí tuyển sinh) theo đợt/ngành/tổ chức.
        -   Tổng hoa hồng đã/đang/ sẽ chi trả.
-   **Yêu cầu hệ thống**:
    -   API báo cáo:
        -   Cho phép filter theo khoảng thời gian, đợt, ngành, tổ chức, CTV.
        -   Trả về số liệu tổng hợp + chi tiết (tùy endpoint).
    -   Xuất Excel:
        -   Hệ thống có thể cung cấp endpoint export báo cáo dưới dạng file theo template.

### 3.6. Phối hợp với Cán bộ hồ sơ và Quản lý tổ chức

-   **Mô tả**:
    -   Kế toán phối hợp:
        -   Với Cán bộ hồ sơ để đảm bảo chỉ những hồ sơ **hợp lệ** mới được tính vào doanh thu chính thức.
        -   Với Quản lý tổ chức để:
            -   Đảm bảo chính sách học phí, lệ phí, hoa hồng đang áp dụng đúng.
-   **Yêu cầu hệ thống**:
    -   API/luồng nghiệp vụ:
        -   Cho phép xem chung một số thông tin (hồ sơ đã đủ điều kiện, đã gửi Trường, v.v.) phục vụ đối soát.
        -   Tách biệt phần quyền cập nhật:
            -   Kế toán cập nhật thanh toán & hoa hồng.
            -   Cán bộ hồ sơ cập nhật điều kiện học thuật.
            -   Quản lý tổ chức cập nhật chính sách và cấu hình.

## 4. Quy tắc quyền hạn & bảo mật cho Kế toán

-   Kế toán **được quyền**:
    -   Xem tất cả `Payment` (theo `PaymentPolicy`).
    -   Xác nhận hoặc từ chối thanh toán (kèm lý do bắt buộc cho các thao tác thay đổi trạng thái nhạy cảm).
    -   Upload và quản lý phiếu thu.
    -   Xem và cập nhật trạng thái chi trả hoa hồng (thông qua các thao tác được thiết kế).
-   Sử dụng **UUID** cho các bản ghi thanh toán để bảo mật URL và ngăn chặn tấn công IDOR khi truy cập chứng từ.
-   Kế toán **không được**:
    -   Thay đổi dữ liệu học thuật của hồ sơ sinh viên.
    -   Thay đổi chính sách hoa hồng ở tầng cấu hình (trừ khi kiêm nhiệm vai trò quản lý).
-   Mọi thao tác tài chính:
    -   Bắt buộc được ghi `audit log` chi tiết, đảm bảo truy xuất được:
        -   Ai đã làm, lúc nào, tác động đến số tiền nào.

## 5. Dữ liệu & tích hợp liên quan đến Kế toán

-   **Dữ liệu chính**:
    -   `Payment`: thông tin thanh toán của sinh viên.
    -   `Commission`, `CommissionItem`: thông tin hoa hồng đã sinh ra.
    -   `Wallet`, `WalletTransaction`: số dư và lịch sử giao dịch ví CTV.
    -   Tham chiếu:
        -   `Student`, `Collaborator`, `Organization`, `Program`, `Intake`, v.v. để đối soát ngữ cảnh.
-   **Tích hợp**:
    -   Google Drive:
        -   Lưu phiếu thu và các chứng từ liên quan (link từ `Payment`).
        -   Visibility trên Google Drive được cấu hình là **Private**. Truy cập thông qua hệ thống CRM có kiểm tra token bảo mật.
    -   Excel/Báo cáo:
        -   Export các báo cáo thu – chi, hoa hồng.

## 6. Tiêu chí thành công theo vai trò Kế toán

-   Kế toán có thể:
    -   Đối soát và chi trả hoa hồng **hoàn toàn trong hệ thống**, không cần file Excel ngoài để tổng hợp thủ công.
    -   Truy vết lại mọi giao dịch thu – chi, biết rõ nguồn gốc và lý do.
-   Hệ thống đảm bảo:
    -   Số liệu thanh toán và hoa hồng **nhất quán** với dữ liệu hồ sơ.
    -   Hạn chế tối đa rủi ro nhầm lẫn, chồng chéo hoặc chi trả trùng.

## 7. Trạng thái các vấn đề mở (Open Items)
-   **Đã giải quyết**:
    -   Bắt buộc nhập lý do khi hoàn trả tiền (`revert`) hoặc chỉnh sửa thông tin bill.
    -   Tự động dọn dẹp số phiếu thu khi hoàn trả để tránh trùng lặp/nhầm lẫn.
    -   Cơ chế hoa hồng tự động sinh khi xác nhận thanh toán thành công.
-   **Cần làm rõ thêm**:
    -   Quy trình phê duyệt nhiều cấp cho các khoản chi hoa hồng lớn.
    -   Tích hợp đối soát tự động với các cổng thanh toán/ngân hàng trong tương lai.
