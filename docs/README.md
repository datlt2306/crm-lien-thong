---
phase: requirements
title: Yêu cầu & Hiểu bài toán
description: Làm rõ bài toán, thu thập yêu cầu và xác định tiêu chí thành công
---

# Yêu cầu & Hiểu bài toán

> **Ghi chú định hướng kiến trúc:** Toàn bộ hệ thống CRM Tuyển sinh Liên thông được thiết kế theo mô hình **API-first (Backend-only)**. Tài liệu này đóng vai trò **SRS (Software Requirements Specification)** và là tài liệu hướng dẫn nghiệp vụ cho các API backend.

## 1. Mô tả bài toán

**Chúng ta đang giải quyết vấn đề gì?**

Hiện tại, quy trình tuyển sinh liên thông đang được vận hành chủ yếu bằng phương pháp thủ công (form đăng ký đơn giản, Excel rời rạc, gọi điện và nhắc hồ sơ thủ công), dẫn đến các vấn đề sau:

-   Sinh viên chỉ điền ít thông tin ban đầu, thiếu dữ liệu để đánh giá điều kiện học tập.
-   Hồ sơ rời rạc, cô Ly phải gọi điện từng sinh viên để nhắc nộp hồ sơ, kiểm tra thiếu sót và nhập tay rất nhiều.
-   Kiểm tra điều kiện ngành (ngành tốt nghiệp có phù hợp với ngành đăng ký hay không) đang làm thủ công, dễ sai sót.
-   Dữ liệu hồ sơ, thanh toán, hoa hồng và danh sách gửi Trường bị phân tán ở nhiều file Excel và Google Drive.

**Ai đang bị ảnh hưởng?**
-   **Cô Ly:** Quá tải nhập liệu, kiểm tra hồ sơ và nhắc sinh viên.
-   **Cô Vinh:** Khó nắm tổng quan số lượng hồ sơ, chỉ tiêu theo ngành và theo đợt.
-   **Kế toán:** Đối soát và chi trả hoa hồng thủ công, tiềm ẩn rủi ro nhầm lẫn.
-   **CTV:** Không theo dõi được tiến độ hồ sơ sinh viên mình phụ trách.

---

## 2. Mục tiêu & Định hướng

### 2.1 Mục tiêu chính (API-first)
-   **Chuẩn hoá nghiệp vụ:** Toàn bộ quy trình từ đăng ký, duyệt hồ sơ đến thanh toán được quản lý qua API rõ ràng.
-   **Số hoá nhập liệu:** Giảm tối thiểu 50–70% khối lượng nhập tay của cô Ly thông qua việc cho phép sinh viên/CTV tự nhập liệu qua API portal.
-   **Kiểm soát chặt chẽ:** Tự động hoá việc kiểm tra điều kiện ngành, đợt tuyển sinh và chỉ tiêu (Quota) ngay từ tầng backend.
-   **Minh bạch hoá:** Trạng thái hồ sơ và hoa hồng cộng tác viên được cập nhật realtime thông qua API.

### 2.2 Ngoài phạm vi
-   Không xây dựng hệ thống học tập (LMS).
-   Không xử lý nghiệp vụ đào tạo sau khi sinh viên đã nhập học.

---

## 3. Câu chuyện người dùng (User Stories)

-   **Sinh viên:** Tôi muốn đăng nhập để xem trạng thái hồ sơ, tự cập nhật thông tin và tải lên giấy tờ minh chứng để hồ sơ được duyệt nhanh hơn.
-   **Cộng tác viên (CTV):** Tôi muốn tạo lead sinh viên, theo dõi tiến độ hồ sơ và xem tổng quan hoa hồng của mình minh bạch.
-   **Cô Ly (Hồ sơ):** Tôi muốn hệ thống tự kiểm tra điều kiện ngành, hiển thị checklist giấy tờ còn thiếu để giảm rủi ro sai sót và nhập liệu.
-   **Cô Vinh (Quản trị):** Tôi muốn cấu hình ngành, đợt tuyển, chỉ tiêu và xem dashboard tổng thể để nắm bắt tình hình tuyển sinh.
-   **Kế toán:** Tôi muốn đối soát phí và hoa hồng để chi trả chính xác ngay trong hệ thống.

---

## 4. Tiêu chí thành công

-   Ít nhất 70% hồ sơ được sinh viên tự nhập và tải đủ giấy tờ trước khi cô Ly kiểm tra.
-   Thời gian xử lý trung bình một hồ sơ giảm tối thiểu 50%.
-   Không xảy ra trường hợp gửi hồ sơ sai ngành cho Trường.
-   Kế toán có thể đối soát và chi trả hoa hồng hoàn toàn trong hệ thống.
-   Xuất được file Excel đúng định dạng Trường chỉ với một thao tác.

---

## 5. Ràng buộc & Giả định

-   **Backend-only / API-first:** Toàn bộ nghiệp vụ phải thể hiện qua API (REST), không phụ thuộc UI.
-   **Audit Log:** Bắt buộc có log thay đổi dữ liệu cho mọi thao tác quan trọng.
-   **Tích hợp:** Hỗ trợ upload file, liên kết Google Drive và xuất Excel theo template tuyển sinh.
-   **Giả định:** Sinh viên có smartphone để tương tác với hệ thống.

---

## 6. Luồng Kiến trúc Nghiệp vụ API (Core Logic)

Hệ thống vận hành dựa trên các logic cốt lõi sau (đã ánh xạ vào code hiện tại):

### 6.1 Luồng Tuyển sinh & Referral
-   **Tracking:** API sử dụng `ref_id` để định danh CTV và đơn vị (Organization). Logic này được xử lý bởi `RefTrackingService`.
-   **Discovery:** API cung cấp metadata (Majors, Programs, Intakes) dựa trên cấu hình của từng Organization.

### 6.2 Logic Quản lý Chỉ tiêu (Quota) - `QuotaService`
-   **Annual Quota:** Hệ thống ưu tiên kiểm tra chỉ tiêu năm theo (Org, Major, Program, Year).
-   **Cơ chế Consume:** Chỉ tiêu **không** bị trừ khi mới đăng ký. Chỉ tiêu chỉ thực sự bị trừ khi `Payment` được xác nhận (`VERIFIED`).

### 6.3 Hệ thống Hoa hồng - `CommissionService`
-   Tự động tính toán hoa hồng dựa trên cấu hình (Org/Major/Program) ngay khi Payment chuyển trạng thái thành `VERIFIED`.

### 6.4 Phân quyền dữ liệu API - `StudentApiController`
-   Dữ liệu được lọc tự động: CTV chỉ thấy học viên của mình, Owner thấy học viên thuộc đơn vị mình đã nộp tiền, Admin thấy toàn bộ.

---

## 7. Yêu cầu dữ liệu hồ sơ (Data Requirements)

### 7.1 Nhóm hệ thống tự sinh
-   STT, Ngày tạo/cập nhật, Trạng thái hồ sơ, Phân loại hồ sơ, Lệ phí (tự động theo Hệ ĐKLT).

### 7.2 Nhóm sinh viên/CTV tự nhập
-   **Thông tin cá nhân:** Họ tên, Ngày sinh, Nơi sinh, CCCD, Số điện thoại, Email, Dân tộc.
-   **Thông tin THPT:** Tên trường, Mã tỉnh/huyện, Năm tốt nghiệp, Học lực, Hạnh kiểm.
-   **Văn bằng CĐ/TC:** Trường tốt nghiệp, Ngành tốt nghiệp, Xếp loại, Số hiệu bằng, Ngày ký bằng.
-   **Giấy tờ minh chứng (Upload):** Bằng TN CĐ/TC, Bảng điểm, CCCD (2 mặt), Giấy khai sinh, Ảnh cá nhân...

### 7.3 Nhóm Cán bộ hồ sơ hoàn thiện
-   Kiểm tra điều kiện ngành, Ngành/Trường/Hệ đăng ký liên thông chính thức, Ghi chú nghiệp vụ.

---

## 8. Danh sách Feature

### Nhóm Sinh viên & CTV
-   `feature-student-enrollment`: Đăng ký, cập nhật hồ sơ và upload minh chứng.
-   `feature-student-payment`: Theo dõi và gửi xác nhận thanh toán.
-   `feature-ctv-pipeline`: Quản lý danh sách sinh viên theo nhánh và theo dõi hoa hồng.

### Nhóm Quản trị & Hồ sơ
-   `feature-officer-approval`: Duyệt hồ sơ, đánh giá điều kiện ngành.
-   `feature-accountant-verify`: Đối soát thanh toán và chi trả hoa hồng.
-   `feature-config-quota`: Thiết lập chỉ tiêu năm và đợt tuyển sinh.
-   `feature-export-data`: Xuất Excel theo template Trường và tích hợp Google Drive.

---

## 9. Danh sách API Hệ thống

### 9.1 Authentication & Profile
- `POST /api/auth/login`: Đăng nhập.
- `GET /api/auth/me`: Thông tin User & Permissions.

### 9.2 Public Enrollment (Student Portal)
- `GET /ref/{ref_id}`: Lấy Majors, Programs, Intakes theo CTV.
- `POST /ref/{ref_id}`: Gửi form đăng ký sinh viên mới.
- `POST /ref/{ref_id}/payment`: Upload bill thanh toán.

### 9.3 Management (Staff/Admin)
- `GET /api/students`: Danh sách sinh viên (Auto-filter theo Role).
- `GET /api/students/{id}`: Chi tiết hồ sơ & Audit logs.
- `PATCH /api/students/{id}/application`: Duyệt & cập nhật hồ sơ.
- `POST /api/payments/{id}/verify`: Xác nhận thanh toán (Trigger Quota & Commission).
- `GET /api/commissions`: Quản lý hoa hồng và chi trả.
- `GET /api/intakes/{id}/quotas`: Theo dõi chỉ tiêu thực tế.

---

## 10. Ràng buộc Kỹ thuật
- **JSON Standard:** 100% Request/Response.
- **Audit Logging:** Ghi lại mọi thay đổi trạng thái nhạy cảm (Status, Amount).
- **Storage:** File lưu tại `storage/app/public`, metadata lưu trong DB.

