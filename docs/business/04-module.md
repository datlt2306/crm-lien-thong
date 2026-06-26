# 04-module.md - Sơ đồ kiến trúc & Phân rã Module

## 1. Bản đồ Module và Ánh xạ File nguồn

### 1.1 Module 1: Đăng ký & Giới thiệu tuyển sinh (Referral & Lead Registration)
* **Chức năng:** Xử lý việc ghi nhận cookie CTV, hiển thị và lưu thông tin đăng ký của sinh viên qua link giới thiệu.
* **Các file nguồn cốt lõi:**
  * Model: [Student.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/Student.php)
  * Controller: [PublicStudentController.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Http/Controllers/PublicStudentController.php)
  * Service: [RefTrackingService.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/RefTrackingService.php)
  * View: `resources/views/ref-form.blade.php`

### 1.2 Module 2: Quản lý Chỉ tiêu (Quota Management)
* **Chức năng:** Khai báo, phân phối và kiểm soát chỉ tiêu tuyển sinh theo năm học (`AnnualQuota`) và đợt tuyển sinh (`Quota`).
* **Các file nguồn cốt lõi:**
  * Models: [Quota.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/Quota.php), [AnnualQuota.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/AnnualQuota.php), [Intake.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/Intake.php)
  * Service: [QuotaService.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/QuotaService.php)

### 1.3 Module 3: Kế toán & Quản lý Tài chính (Payment & Finance)
* **Chức năng:** Đối soát nộp phí của học viên, cập nhật phiếu thu, chốt chi trả hoa hồng và quản lý số dư Ví CTV.
* **Các file nguồn cốt lõi:**
  * Models: [Payment.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/Payment.php), [Wallet.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/Wallet.php), [WalletTransaction.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/WalletTransaction.php)
  * Services: [StudentFeeService.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/StudentFeeService.php)
  * Controller: [FileController.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Http/Controllers/FileController.php)

### 1.4 Module 4: Quản lý Hoa hồng (Referral Commission)
* **Chức năng:** Định nghĩa chính sách hoa hồng, tự động phân tách/phát sinh hoa hồng chi tiết khi thanh toán thành công và mở khóa hoa hồng khi sinh viên nhập học.
* **Các file nguồn cốt lõi:**
  * Models: [Commission.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/Commission.php), [CommissionItem.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/CommissionItem.php), [CommissionPolicy.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/CommissionPolicy.php)
  * Service: [CommissionService.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/CommissionService.php)

### 1.5 Module 5: Hệ thống Tích hợp & Thông báo (Telegram Bot & Notifications)
* **Chức năng:** Gửi cảnh báo chỉ tiêu, thông báo trạng thái ví qua Email/Telegram; tiếp nhận upload bill nhanh bằng cách chat hoặc gửi ảnh reply trên Telegram.
* **Các file nguồn cốt lõi:**
  * Controller: [TelegramWebhookController.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Http/Controllers/TelegramWebhookController.php)
  * Services: [TelegramBotService.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/TelegramBotService.php), [NotificationService.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/NotificationService.php)
  * Model: [NotificationPreference.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/NotificationPreference.php)

---

## 2. Kiến trúc dịch vụ (Service Layer & Interactions)
Hệ thống tuân thủ chặt chẽ nguyên lý **Thin Controller - Thick Service & Model**.
* **`StudentApiController` & `PublicStudentController`** đóng vai trò giao tiếp, lọc/validate request cơ bản và trả về JSON.
* **`CommissionService`** là trung tâm điều phối tài chính của CTV. Nó lắng nghe sự kiện từ `Payment` và tương tác trực tiếp với `Wallet` để cập nhật biến động số dư.
* **`QuotaService`** tương tác với cả `Student` (khi chuyển ngành/chuyển đợt) và `Payment` (khi được duyệt nộp tiền) để điều chỉnh chỉ tiêu thực tế chính xác bằng cơ chế khóa bản ghi `lockForUpdate()`.

### Evidence:
* lockForUpdate trong QuotaService: [QuotaService.php:L28](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/QuotaService.php#L28)
* Độc lập của Service hoa hồng: [CommissionService.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Services/CommissionService.php)
