# 03-user-roles.md - Vai trò người dùng & Phân quyền dữ liệu

## 1. Danh sách các vai trò (Roles) trong hệ thống
Hệ thống sử dụng gói `spatie/laravel-permission` để quản lý phân quyền và nhóm vai trò:
* **`super_admin`:** Toàn quyền hệ thống, không bị giới hạn dữ liệu.
* **`admin` / `organization_owner`:** Chủ đơn vị quản lý, xem được toàn bộ thông tin học viên của đơn vị mình.
* **`document`:** Cán bộ hồ sơ, xem và sửa dữ liệu học viên (chỉ những người đã gửi bill thanh toán).
* **`accountant`:** Kế toán, xem và sửa thông tin thanh toán (payment), xác nhận nộp tiền, đối soát hoa hồng.
* **`collaborator`:** Cộng tác viên, chỉ được xem danh sách học viên do chính mình giới thiệu thông qua mã Ref, xem ví và hoa hồng cá nhân.

### Evidence:
* Ánh xạ phân quyền trong API: [StudentApiController.php:L102-129](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Http/Controllers/Api/StudentApiController.php#L102-L129)
* Cấu hình điều kiện canAccess của Resource: [AuditLogResource.php:L31-34](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Filament/Resources/AuditLogResource.php#L31-L34)
* Trait và Model User: [User.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/User.php)

---

## 2. Ma trận phân quyền (Permission Matrix)

| Chức năng / Quyền hạn | `super_admin` | `document` | `accountant` | `collaborator` |
| :--- | :---: | :---: | :---: | :---: |
| **Quản lý Cấu hình hệ thống (Ngành, Đợt tuyển, Quota)** | **Có** | Không | Không | Không |
| **Tạo mới hồ sơ sinh viên** | **Có** | **Có** | Không | **Có (chỉ lead mình)** |
| **Cập nhật thông tin sinh viên** | **Có** | **Có** | Không | **Có (chỉ khi chưa verified)** |
| **Xóa hồ sơ sinh viên (Mềm)** | **Có** | **Có** | Không | Không |
| **Xem minh chứng chuyển khoản (Bill)** | **Có** | **Có** | **Có** | **Có (chỉ lead mình)** |
| **Xác minh thanh toán (Verify Payment)** | **Có** | Không | **Có** | Không |
| **Hủy/Hoàn trả xác minh nộp tiền (Revert)** | **Có** | **Có** | Không | Không |
| **Chốt sổ & Chi trả hoa hồng** | **Có** | Không | **Có** | Không |
| **Xem số dư Ví & Rút hoa hồng** | **Có** | Không | Không | **Có (chỉ ví mình)** |
| **Xem Audit Log toàn hệ thống** | **Có** | Không | Không | Không |

### Evidence:
* Quyền xác minh thanh toán: [EditStudent.php:L369-377](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Filament/Resources/Students/Pages/EditStudent.php#L369-L377)
* Quyền hủy/hoàn trả xác minh: [EditStudent.php:L441-451](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Filament/Resources/Students/Pages/EditStudent.php#L441-L451)
* Quyền xem bill chuyển khoản: [FileController.php:L19-33](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Http/Controllers/FileController.php#L19-L33)
* Phân quyền xem log: [AuditLogResource.php:L31-34](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Filament/Resources/AuditLogResource.php#L31-L34)

---

## 3. Quy tắc phân vùng dữ liệu (Data Isolation Rules)
* **Cô lập CTV:** CTV được lọc dữ liệu cứng qua `collaborator_id`. Họ không thể truy cập, xem hoặc chỉnh sửa bất kỳ học viên nào không phải do họ giới thiệu.
* **Quyền canAccess của trang Chỉnh sửa học viên:** CTV chỉ được phép chỉnh sửa thông tin học viên khi học viên đó **chưa được xác nhận nộp tiền** (tức là chưa phát sinh `Payment::STATUS_VERIFIED`).
* **Đối tượng tiếp cận của Staff:** Cán bộ hồ sơ (`document`) và Kế toán (`accountant`) chỉ nhìn thấy các học viên ở trạng thái `submitted` hoặc `verified` (tức là đã nộp bill hoặc đã duyệt thanh toán), giúp họ tập trung xử lý hồ sơ thực tế, tránh các lead ảo (ở trạng thái `new` / `contacted`).

### Evidence:
* Lọc dữ liệu CTV: [StudentApiController.php:L109-114](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Http/Controllers/Api/StudentApiController.php#L109-L114)
* Chặn CTV sửa khi đã nộp tiền: [EditStudent.php:L607-623](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Filament/Resources/Students/Pages/EditStudent.php#L607-L623)
* Lọc dữ liệu Staff: [StudentApiController.php:L117-125](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Http/Controllers/Api/StudentApiController.php#L117-L125)
