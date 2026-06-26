# 07-api.md - Danh sách API & Tương tác Hệ thống

## 1. Authentication & Profile API
* **Đăng nhập Google OAuth2 (Google Login):**
  * `GET /admin/login/google` -> Chuyển hướng người dùng sang trang xác thực của Google.
  * `GET /admin/login/google/callback` -> Google callback, xác thực email, cập nhật trường `google_id`, `google_avatar` và đăng nhập người dùng vào phiên web.
* **Evidence:** [GoogleController.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Http/Controllers/Auth/GoogleController.php)

---

## 2. Public Portal API (Dành cho Sinh viên và Link CTV)
* **Lấy thông tin hiển thị Form đăng ký:**
  * `GET /ref/{ref_id}`
  * **Mô tả:** Lọc và hiển thị danh sách đợt tuyển sinh đang mở (`intakes`), các chương trình học còn chỉ tiêu tuyển sinh thuộc đợt tuyển đó.
* **Gửi hồ sơ đăng ký mới:**
  * `POST /ref/{ref_id}`
  * **Mô tả:** Tiếp nhận thông tin học viên, kiểm tra reCAPTCHA, kiểm tra trùng lặp Email/Phone, tạo bản ghi Student & Payment mặc định (STATUS_NOT_PAID), tăng chỉ tiêu chờ duyệt (`pending_quota`).
* **Nộp hóa đơn (bill) chuyển khoản phí tuyển sinh:**
  * `POST /ref/{ref_id}/payment`
  * **Mô tả:** Nhận file bill chuyển khoản, đổi tên file theo định dạng chuẩn `"Hóa đơn đăng ký/2026/Mã_Tên_Ngành_Hệ.ext"`, cập nhật trạng thái Payment sang `submitted` (chờ xác minh), cập nhật trạng thái Student sang `submitted`.
* **Theo dõi tiến độ hồ sơ cá nhân:**
  * `GET /profile-tracking?code={profile_code}`
  * **Mô tả:** Tra cứu thông tin hồ sơ của sinh viên dựa trên mã hồ sơ (ví dụ: HS2026GY6P101). Hiển thị chi tiết trạng thái duyệt hồ sơ và tình trạng thanh toán.

### Evidence:
* [PublicStudentController.php](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Http/Controllers/PublicStudentController.php)
* Cấu hình reCAPTCHA: [PublicStudentController.php:L120-129](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Http/Controllers/PublicStudentController.php#L120-L129)
* Upload và phân bổ file bill: [PublicStudentController.php:L263-294](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Http/Controllers/PublicStudentController.php#L263-L294)

---

## 3. Management Dashboard API (Dành cho Cán bộ & Admin)
* **Lấy danh sách học viên (Index):**
  * `GET /api/students`
  * **Mô tả:** Trả về danh sách học viên có phân trang. Tự động áp dụng bộ lọc dữ liệu (CTV chỉ xem học viên của mình, Kế toán/Hồ sơ chỉ thấy học viên đã nộp bill hoặc đã xác nhận).
* **Xem chi tiết hồ sơ học viên:**
  * `GET /api/students/{id}`
  * **Mô tả:** Trả về chi tiết trường thông tin của học viên và liên kết quan hệ (`collaborator`, `major`, `intake`, `payment`).
* **Đọc/Tải file minh chứng an toàn (IDOR Prevention):**
  * `GET /files/bill/{paymentId}` (Nhân viên)
  * `GET /public/files/bill/{paymentUuid}?token={token}` (Sinh viên/Công khai)
  * **Mô tả:** Đọc file từ Storage (Google Drive hoặc Local) và trả về stream hiển thị trực tiếp. Đối với sinh viên, bắt buộc phải truyền kèm `token` mã hóa SHA256 dựa trên UUID để ngăn chặn tấn công IDOR.

### Evidence:
* Phân quyền index: [StudentApiController.php:L20-55](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Http/Controllers/Api/StudentApiController.php#L20-L55)
* Chi tiết hồ sơ: [StudentApiController.php:L63-91](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Http/Controllers/Api/StudentApiController.php#L63-L91)
* Tải file an toàn & Token SHA256: [FileController.php:L36-50](file:///Users/ken/Folders/Projects/Herd/crm-lien-thong/app/Http/Controllers/FileController.php#L36-L50)
