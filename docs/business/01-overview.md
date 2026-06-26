# 01-overview.md - Tổng quan dự án CRM Tuyển sinh Liên thông

## 1. Bối cảnh dự án (Project Context)
Dự án CRM Tuyển sinh Liên thông là một giải pháp API-first / Backend-first được thiết kế nhằm số hóa, tối ưu hóa và tự động hóa quy trình tuyển sinh, quản lý hồ sơ, tài chính và phân phối hoa hồng cộng tác viên (referral commissions). Hệ thống giúp giảm thiểu quy trình xử lý thủ công bằng giấy tờ và Excel, giảm tải khối lượng công việc hành chính và nâng cao tính minh bạch tài chính.

### Tài liệu tham khảo từ codebase:
* Định hướng API-first: [docs/README.md](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/docs/README.md)
* Cấu hình Route API: `routes/api.php`
* Quản trị tập trung (Filament CMS): `app/Filament/Resources/`

---

## 2. Mục tiêu hệ thống (System Objectives)
* **Số hóa quy trình nộp hồ sơ:** Cho phép Cộng tác viên (CTV) và Sinh viên tự nhập liệu và tải ảnh minh chứng trực tuyến, giúp giảm tải đến 50-70% thao tác nhập liệu thủ công.
* **Tự động hóa đối soát và tính hoa hồng:** Hệ thống tự động tính toán, phân phối hoa hồng và cập nhật số dư ví CTV ngay khi thanh toán học phí của sinh viên được xác minh.
* **Kiểm soát chỉ tiêu (Quota) chặt chẽ:** Tự động kiểm tra và khóa đăng ký khi ngành học/hệ đào tạo thuộc năm/đợt tuyển sinh đó đạt giới hạn chỉ tiêu (Quota).
* **Đảm bảo tính vẹn toàn dữ liệu:** Mọi hoạt động nhạy cảm về thông tin học viên, số dư tài chính hoặc thao tác cấu hình đều được ghi nhận chi tiết qua cơ chế tự động ghi nhật ký hệ thống (Audit Log).

---

## 3. Đối tượng người dùng chính (Target Users)
Hệ thống phân phối quyền hạn rõ ràng thông qua Role & Permissions:
1. **Quản trị viên cấp cao (Super Admin):** Có toàn quyền cấu hình hệ thống, quản lý người dùng, duyệt CTV và thiết lập chính sách hoa hồng.
2. **Cán bộ hồ sơ (Document Officer):** Phụ trách tiếp nhận, đối chiếu điều kiện tuyển sinh, kiểm tra checklist hồ sơ sinh viên.
3. **Kế toán (Accountant):** Thực hiện đối soát học phí sinh viên đã đóng, duyệt chi trả hoa hồng cho CTV.
4. **Cộng tác viên (Collaborator):** Người giới thiệu sinh viên, theo dõi danh sách lead, nộp minh chứng chuyển khoản (bill) và rút hoa hồng tích lũy.
5. **Sinh viên (Student):** Người học đăng ký thông tin cá nhân, cập nhật hồ sơ văn bằng THPT/CĐ và theo dõi trạng thái phê duyệt.

### Evidence:
* Phân quyền truy cập tài nguyên: [StudentApiController.php:L102-129](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Http/Controllers/Api/StudentApiController.php#L102-L129)
* Trait phân quyền của Filament: [User.php:L27-30](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Models/User.php#L27-L30)
