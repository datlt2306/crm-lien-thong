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
    -   Sau khi sinh viên nộp tiền (qua CTV hoặc **đi trực tiếp – walkin**), Kế toán phải:
        -   Đối chiếu giao dịch thực tế.
        -   Xác nhận vào hệ thống **thông qua action riêng cho Kế toán**, không sửa tay trạng thái trong form hồ sơ.
    -   Flow chuẩn trong UI (Filament `Students`):
        -   Bước 1 – “Đã nộp tiền”: CTV / tổ chức / văn phòng tuyển sinh dùng action **`Xác nhận đã nộp tiền`** trên màn `Students`/`EditStudent` để:
            -   Tạo/cập nhật `Payment` với `amount` và `status = SUBMITTED`.
            -   Cập nhật `students.status` sang `SUBMITTED`.
        -   Bước 2 – “Kế toán xác nhận”: Kế toán dùng action riêng **`Xác nhận thanh toán`** (chỉ hiển thị cho role `accountant`/`document`/`organization_owner`/`super_admin`) để:
            -   Kiểm tra số tiền (`amount`) và sửa nếu cần.
            -   Gọi logic backend `markAsVerified` cho `Payment` → chuyển `status` sang `VERIFIED`.
            -   Gọi `CommissionService::createCommissionFromPayment($payment)` để sinh commission (nếu hồ sơ có `collaborator_id` / `primary_collaborator_id`).
    -   Quy tắc cho hồ sơ walkin:
        -   Hồ sơ có `source = walkin` bắt buộc `collaborator_id = null` → `primary_collaborator_id` cũng null.
        -   Kế toán vẫn xác nhận thanh toán bình thường (trừ Quota, ghi nhận doanh thu), **nhưng không sinh commission** do không có CTV.
-   **Căn cứ từ `PaymentPolicy`**:
    -   Hàm `verify` cho phép:
        -   User có quyền `verify_payment` hoặc có role `accountant`, `document` được xác nhận thanh toán.
-   **Yêu cầu hệ thống**:
    -   API xác nhận thanh toán:
        -   Chỉ áp dụng cho `Payment` ở các trạng thái hợp lệ (ví dụ: submitted).
        -   Cho phép Kế toán:
            -   Đặt trạng thái `verified` (hoặc tương đương).
            -   Ghi chú (ví dụ: mã giao dịch, kênh nhận tiền, ghi chú nội bộ).
    -   Nếu từ chối / huỷ bỏ (Cancel):
        -   Ghi rõ lý do từ chối (thiếu chứng từ, sai số tiền, chuyển nhầm, v.v.).
        -   **Quan trọng - Khôi phục Chỉ tiêu (Quota Restoration)**: Khi Payment bị chuyển khỏi trạng thái `VERIFIED` (sang `REJECTED`, `CANCELLED` hoặc trả về `PENDING`), hệ thống bắt buộc tự động **hoàn trả (auto-refund) 1 slot chỉ tiêu (Quota)** lại cho Tổ chức để tránh tình trạng rò rỉ (Quota Leak).
        -   Có thể đẩy thông báo cho sinh viên/CTV/Cán bộ hồ sơ.

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
        -   Không được xóa hoàn toàn chứng từ đã gắn với giao dịch nếu hệ thống yêu cầu lưu trữ lâu dài (trừ khi có luồng hủy bỏ có kiểm soát).

### 3.4. Quản lý hoa hồng Cộng tác viên

-   **Mô tả**:
    -   Dựa trên các `Payment` đã được xác nhận và chính sách hoa hồng:
        -   Hệ thống tính ra hoa hồng tương ứng cho CTV.
    -   Kế toán chịu trách nhiệm:
        -   Đối soát số liệu.
        -   Thực hiện chi trả hoa hồng (chuyển khoản/tiền mặt).
-   **Liên quan đến model**:
    -   `Commission`, `CommissionItem`, `Wallet`, `WalletTransaction`, `Collaborator`.
-   **Yêu cầu hệ thống**:
    -   API cho Kế toán:
        -   Xem danh sách hoa hồng theo:
            -   Đợt tuyển sinh, thời gian, tổ chức, CTV.
        -   Xem chi tiết từng khoản hoa hồng gắn với hồ sơ/thanh toán.
    -   Khi thực hiện chi trả:
        -   **Bảo mật Ví (Wallet Security)**: Hệ thống nghiêm cấm mọi giao dịch ví (Deposit, Withdraw, Transfer) có giá trị `< 0` hoặc `= 0` (Chặn Negative Amount Exploit).
        -   Không cho phép chuyển tiền vào chính ví của người gửi (Loop Transaction).
        -   Cập nhật số dư `Wallet` và tạo `WalletTransaction` phải diễn ra trong môi trường khoá DB (`DB::transaction` + `lockForUpdate`) nhằm chặn Race Condition.
        -   Ghi nhận giao dịch vào `WalletTransaction`:
            -   Loại giao dịch: chi hoa hồng.
            -   Số tiền.
            -   CTV nhận.
            -   Hình thức chi (nếu cần).
    -   Có thể lock các khoản hoa hồng đã chi trả, tránh chỉnh sửa ngược chiều không có log.

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
    -   Xác nhận hoặc từ chối thanh toán.
    -   Upload và quản lý phiếu thu.
    -   Xem và cập nhật trạng thái chi trả hoa hồng (thông qua các thao tác được thiết kế).
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
    -   Excel/Báo cáo:
        -   Export các báo cáo thu – chi, hoa hồng.

## 6. Tiêu chí thành công theo vai trò Kế toán

-   Kế toán có thể:
    -   Đối soát và chi trả hoa hồng **hoàn toàn trong hệ thống**, không cần file Excel ngoài để tổng hợp thủ công.
    -   Truy vết lại mọi giao dịch thu – chi, biết rõ nguồn gốc và lý do.
-   Hệ thống đảm bảo:
    -   Số liệu thanh toán và hoa hồng **nhất quán** với dữ liệu hồ sơ.
    -   Hạn chế tối đa rủi ro nhầm lẫn, chồng chéo hoặc chi trả trùng.

## 7. Open Items riêng cho vai trò Kế toán

-   Cần làm rõ:
    -   Chi tiết chính sách hoa hồng:
        -   Cách tính theo đợt/ngành/loại chương trình.
        -   Điều kiện “chốt” để được tính hoa hồng (chỉ khi sinh viên nhập học, hay chỉ cần nộp lệ phí).
    -   Quy trình phê duyệt nhiều bước:
        -   Có cần thêm bước duyệt của quản lý trước khi chi khoản lớn hay không.
    -   Tích hợp với:
        -   Hệ thống kế toán nội bộ (nếu có).
        -   Ngân hàng hoặc cổng thanh toán (nếu cần tự động đối soát).
