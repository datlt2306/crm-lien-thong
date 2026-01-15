---
phase: requirements
role: quan_ly_to_chuc
title: SRS – Vai trò Quản lý tổ chức (Cô Vinh)
description: Đặc tả yêu cầu nghiệp vụ và hành vi hệ thống cho vai trò Quản lý tổ chức trong CRM Tuyển sinh Liên thông
---

## 1. Giới thiệu & Bối cảnh

### 1.1. Mô tả vai trò

-   **Quản lý tổ chức (Cô Vinh)** là người:
    -   Quản trị tổng thể hoạt động tuyển sinh liên thông của một hoặc nhiều đơn vị/tổ chức.
    -   Thiết lập cấu hình chính:
        -   Ngành, chương trình, đợt tuyển sinh, quota/chỉ tiêu.
        -   Chính sách hoa hồng, phân quyền nội bộ.
    -   Theo dõi dashboard tổng quan về hồ sơ, doanh thu, quota, hiệu quả CTV.

### 1.2. Phạm vi SRS cho Quản lý tổ chức

-   Mô tả các nghiệp vụ backend cho phép Quản lý tổ chức:
    -   Quản lý thông tin `Organization` và các thành viên (Owner, CTV, document, accountant, v.v.).
    -   Quản lý danh mục ngành (`Major`), chương trình (`Program`), đợt tuyển sinh (`Intake`), quota (`Quota` / IntakeQuotas).
    -   Cấu hình chính sách hoa hồng (`CommissionPolicy`, `CommissionService`).
    -   Xem dashboard, báo cáo tổng hợp (sử dụng `DashboardCacheService`).

## 2. Mục tiêu theo vai trò

-   Đảm bảo:
    -   Quy trình tuyển sinh được cấu hình nhất quán, đúng chỉ tiêu, đúng ngành.
    -   Dữ liệu hồ sơ, thanh toán, hoa hồng được gom về **một nguồn tin cậy** cho quản lý.
-   Tạo nền tảng:
    -   Dễ dàng mở rộng thêm đợt, chương trình, chính sách mới mà không cần sửa nhiều trong code.

## 3. Use Case chính cho Quản lý tổ chức

### 3.1. Quản lý thông tin tổ chức (Organization)

-   **Mô tả**:
    -   Quản lý tổ chức cần xem và cập nhật thông tin đơn vị mình phụ trách.
-   **Căn cứ từ `OrganizationPolicy`**:
    -   `viewAny`, `view`, `update` cho phép:
        -   `super_admin` xem/cập nhật tất cả.
        -   `organization_owner` chỉ được thao tác trên `Organization` mà mình là `organization_owner_id`.
    -   `create`, `delete`, `restore`, `forceDelete` chỉ dành cho `super_admin`.
-   **Yêu cầu hệ thống**:
    -   API cho phép `organization_owner`:
        -   Xem chi tiết tổ chức của mình.
        -   Cập nhật một số thông tin cho phép (tên, thông tin liên hệ, cấu hình hiển thị, v.v.).
    -   Không được tạo hoặc xoá tổ chức mới (thuộc quyền nền tảng).

### 3.2. Quản lý thành viên tổ chức & phân quyền

-   **Mô tả**:
    -   Quản lý tổ chức cần quản lý:
        -   Danh sách người dùng thuộc tổ chức (CTV, document, accountant, v.v.).
        -   Phân bổ vai trò/phân quyền trong phạm vi tổ chức.
-   **Căn cứ từ `UserPolicy` & `CollaboratorPolicy`**:
    -   `UserPolicy`:
        -   `organization_owner` có thể:
            -   `viewAny`, `create`, `update`, `delete`, `restore`, `forceDelete` user **trong tổ chức của mình** (thông qua mapping với `Collaborator` và `Organization`).
    -   `CollaboratorPolicy`:
        -   `organization_owner` có thể quản lý (xem, tạo, cập nhật, xoá, approve, reject) `Collaborator` trong `organization_id` của mình.
-   **Yêu cầu hệ thống**:
    -   API cho phép `organization_owner`:
        -   Tạo tài khoản CTV/nhân sự nội bộ gắn với tổ chức.
        -   Phân vai trò trong phạm vi được cho phép (ctv, document, accountant tổ chức, v.v.).
        -   Khoá/mở khoá tài khoản nội bộ (chấm dứt hợp tác, tạm dừng).

### 3.3. Quản lý ngành, chương trình, đợt tuyển sinh và quota

-   **Mô tả**:
    -   Quản lý tổ chức cần:
        -   Tạo/điều chỉnh ngành (`Major`), chương trình (`Program`).
        -   Tạo đợt tuyển sinh (`Intake`) và gán chỉ tiêu (quota) theo ngành/chương trình.
-   **Liên quan tới Resources/Models**:
    -   `Majors/MajorResource`, `Programs/ProgramResource`, `Intakes/IntakeResource`, `Quotas/QuotaResource`, `IntakeQuotas`.
-   **Yêu cầu hệ thống**:
    -   API cho phép:
        -   Tạo, cập nhật, vô hiệu hoá ngành và chương trình trong phạm vi tổ chức.
        -   Tạo đợt tuyển sinh:
            -   Thời gian bắt đầu/kết thúc.
            -   Áp dụng cho ngành/chương trình nào.
        -   Thiết lập quota:
            -   Số lượng chỉ tiêu tổng, theo ngành, theo CTV (nếu có).
    -   Hệ thống cần:
        -   Cảnh báo khi quota gần đầy (`QuotaWarningNotification`).
        -   Không cho phép tạo hồ sơ/đăng ký mới vượt quá quota (hoặc phải ghi nhận là “vượt quota” nếu cho phép).

### 3.4. Cấu hình chính sách hoa hồng

-   **Mô tả**:
    -   Quản lý tổ chức định nghĩa:
        -   Cách tính hoa hồng cho CTV (theo đợt/ngành/hình thức, nhiều tầng nếu có).
-   **Liên quan tới**:
-   `CommissionPolicy`, `CommissionService`, các `Filament` resources về Commission/CommissionPolicies.
-   **Yêu cầu hệ thống**:
    -   API cho phép:
        -   Tạo và cập nhật chính sách hoa hồng:
            -   Tỷ lệ hoa hồng.
            -   Điều kiện áp dụng (đạt trạng thái nào, chương trình nào).
            -   Áp dụng cho tầng nào (CTV trực tiếp, upline, v.v.) nếu hỗ trợ.
        -   Kích hoạt / vô hiệu hoá chính sách.
    -   Khi có thay đổi chính sách:
        -   Hệ thống phải quy định rõ:
            -   Áp dụng cho giao dịch mới từ thời điểm nào.
            -   Không làm thay đổi kết quả các giao dịch quá khứ đã chốt.

### 3.5. Xem dashboard & báo cáo tổng quan

-   **Mô tả**:
    -   Quản lý tổ chức cần có cái nhìn tổng quan:
        -   Số lượng hồ sơ theo trạng thái.
        -   Số lượng hồ sơ theo ngành, theo CTV, theo đợt.
        -   Doanh thu và chi phí hoa hồng ở mức high-level.
-   **Liên quan tới**:
    -   `DashboardCacheService` và các bảng `Student`, `Payment`, `Commission`, `Quota`.
-   **Yêu cầu hệ thống**:
    -   API/dashboard:
        -   Tổng hợp dữ liệu đã được cache (tránh query nặng trực tiếp mỗi lần).
        -   Phân quyền để `organization_owner` chỉ nhìn thấy dữ liệu liên quan tới tổ chức của mình.
    -   Các chỉ số gợi ý:
        -   Số hồ sơ mới theo thời gian.
        -   Tỷ lệ hồ sơ hoàn thiện/đạt điều kiện.
        -   Tỷ lệ sử dụng quota.
        -   Doanh thu ước tính / thực thu.

### 3.6. Chính sách và rule vận hành

-   **Mô tả**:
    -   Quản lý tổ chức thiết lập một số rule vận hành như:
        -   Quy tắc khoá field sau khi nộp hồ sơ (ở mức cấu hình tổng).
        -   Các ngưỡng cảnh báo về quota, deadline đợt, v.v.
-   **Yêu cầu hệ thống**:
    -   API cấu hình:
        -   Cho phép đặt các tham số chính (nếu không hard-code):
            -   Thời điểm khoá chỉnh sửa.
            -   Số lần cho phép đổi đợt/ngành.
            -   Ngưỡng % quota để bắn cảnh báo.
    -   Các cấu hình này:
        -   Được lưu rõ ràng theo tổ chức hoặc toàn hệ thống.
        -   Không bị người dùng khác thay đổi tuỳ tiện.

## 4. Quy tắc quyền hạn & bảo mật cho Quản lý tổ chức

-   Quản lý tổ chức (role `organization_owner`) **được quyền**:
    -   Xem và cập nhật tổ chức của mình (`OrganizationPolicy`).
    -   Quản lý user và collaborator trong tổ chức (`UserPolicy`, `CollaboratorPolicy`).
    -   Cấu hình các danh mục và rule trong phạm vi tổ chức (ngành, chương trình, đợt, quota, hoa hồng) theo thiết kế hệ thống.
-   Quản lý tổ chức **không được**:
    -   Truy cập hoặc chỉnh sửa dữ liệu thuộc tổ chức khác.
    -   Ghi đè cấu hình toàn hệ thống thuộc quyền `super_admin` (nếu tách 2 tầng).
-   Mọi thao tác cấu hình:
    -   Phải được log lại để:
        -   Truy vết khi cần.
        -   Giải trình với các bên liên quan (kế toán, ban giám hiệu, v.v.).

## 5. Dữ liệu & tích hợp liên quan đến Quản lý tổ chức

-   **Dữ liệu chính**:
    -   `Organization`: thông tin đơn vị.
    -   `User`, `Collaborator`: người dùng nội bộ và CTV.
    -   `Program`, `Major`, `Intake`, `Quota`, `IntakeQuota`: cấu hình tuyển sinh.
    -   `CommissionPolicy`, `Commission`, `CommissionItem`: chính sách và kết quả hoa hồng.
    -   `Student`, `Payment`: dữ liệu vận hành thực tế để làm báo cáo.
-   **Tích hợp**:
    -   Google Drive:
        -   Cấu trúc thư mục theo tổ chức/đợt có thể liên quan đến cách quản lý file, nhưng chi tiết kỹ thuật có thể do hệ thống nền tảng quyết định.
    -   Excel/Báo cáo:
        -   Export báo cáo tổng hợp theo tổ chức.

## 6. Tiêu chí thành công theo vai trò Quản lý tổ chức

-   Quản lý tổ chức có thể:
    -   Theo dõi toàn cảnh tuyển sinh của đơn vị trên một dashboard duy nhất.
    -   Chủ động cấu hình ngành/đợt/quota mà không cần IT can thiệp thường xuyên.
-   Hệ thống đảm bảo:
    -   Không có tình trạng vượt quota ngoài kiểm soát (hoặc nếu có thì được log rõ).
    -   Dữ liệu báo cáo giữa các bên (tuyển sinh, kế toán, CTV) khớp nhau từ một nguồn.

## 7. Open Items riêng cho vai trò Quản lý tổ chức

-   Cần làm rõ:
    -   Ranh giới quyền giữa `super_admin` và `organization_owner` cho từng loại cấu hình.
    -   Mô hình nhiều tổ chức:
        -   Một `organization_owner` có thể quản lý nhiều `Organization` hay chỉ một?
    -   Chi tiết mapping ngành đầu vào – đầu ra:
        -   Ai chịu trách nhiệm nhập data mapping.
        -   Có thay đổi theo đợt hoặc theo năm học hay không.
