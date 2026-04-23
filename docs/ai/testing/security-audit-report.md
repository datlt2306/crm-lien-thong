# BÁO CÁO KIỂM TRA AN NINH HỆ THỐNG (SECURITY AUDIT)

*Ngày thực hiện: 23/04/2026*
*Người thực hiện: Antigravity*

---

## A. Tổng quan Rủi ro & Điểm mù (Executive Summary)

Hệ thống đang tồn tại **02 lỗ hổng nghiêm trọng (High/Critical)** liên quan đến việc truy cập dữ liệu trái phép thông qua các ID dự đoán được (Predictable IDs). 

- **Điểm mù:** Quá chú trọng vào phân quyền trong Admin (Filament/Spatie) nhưng lại bỏ ngỏ hoàn toàn các Route công khai (Public Routes) phục vụ học viên tra cứu.
- **Rủi ro thực tế:** Đối thủ hoặc kẻ xấu có thể quét (scrape) toàn bộ danh sách học viên, số điện thoại, email và các chứng từ tài chính nhạy cảm chỉ trong vài phút.

---

## B. Danh sách lỗ hổng (Vulnerabilities)

### 1. Lỗ hổng IDOR trên tệp tin thanh toán (Critical)
*   **Vị trí:** `app/Http/Controllers/FileController.php::publicViewBill`
*   **Mô tả:** Hàm này cho phép xem hóa đơn/phiếu thu mà **không cần đăng nhập** và chỉ dựa vào `paymentId` (là số tăng dần).
*   **Cách khai thác thực tế:** Kẻ tấn công chỉ cần thay đổi số ID cuối URL (vd: `.../public/files/bill/1`, `.../public/files/bill/2`, ...) là có thể xem và tải về toàn bộ hóa đơn của hệ thống.
*   **Hậu quả:** Lộ diện thông tin chuyển khoản, số tiền, tên học viên và tài khoản ngân hàng của tổ chức.
*   **Cách sửa:** 
    1. Thay đổi `paymentId` từ số tự động tăng sang **UUID**.
    2. Yêu cầu thêm một token bảo mật hoặc mã hồ sơ (profile_code) đi kèm để xác minh quyền truy cập.
*   **Ưu tiên:** **Khẩn cấp (Xử lý ngay)**

### 2. Quét dữ liệu học viên qua mã hồ sơ dễ đoán (High)
*   **Vị trí:** `app/Models/Student.php` (Hàm `booted`) & `app/Http/Controllers/PublicStudentController.php::showProfileTracking`
*   **Mô tả:** `profile_code` được sinh theo công thức: `HS` + `Năm` + `ID học viên` (vd: `HS2026000194`). Vì ID là số tăng dần, mã này hoàn toàn có thể đoán được.
*   **Cách khai thác thực tế:** Viết một script đơn giản chạy vòng lặp từ `HS2026000001` đến `HS2026010000` gửi đến route `/track/{profile_code}` để thu thập toàn bộ database học viên.
*   **Hậu quả:** Mất toàn bộ dữ liệu khách hàng (Leads/Students) vào tay đối thủ. Vi phạm nghiêm trọng bảo mật thông tin cá nhân.
*   **Cách sửa:** Sử dụng chuỗi ngẫu nhiên (Random String) hoặc HashId để tạo `profile_code` thay vì dùng ID trực tiếp.
*   **Ưu tiên:** **Cao**

### 3. Lưu trữ tệp tin nhạy cảm ở chế độ "Public" trên Google Drive (Medium)
*   **Vị trí:** `app/Http/Controllers/PublicStudentController.php::submitPayment`
*   **Mô tả:** Khi lưu tệp tin hóa đơn, hệ thống đặt `'visibility' => 'public'`.
*   **Cách khai thác thực tế:** Nếu link Google Drive trực tiếp bị lộ, bất kỳ ai cũng có thể xem mà không cần qua app.
*   **Hậu quả:** Giảm khả năng kiểm soát dữ liệu của ứng dụng.
*   **Cách sửa:** Chuyển sang `'visibility' => 'private'`. Sử dụng link tạm thời (Signed URLs) để hiển thị file.
*   **Ưu tiên:** **Trung bình**

---

## D. Priority Roadmap

### 1. Việc cần làm ngay (24h)
- Khóa ngay Route `public.files.bill.view` hoặc thêm kiểm tra `hash` đi kèm.
- Chuyển `visibility` file upload từ `public` sang `private`.

### 2. Trong 7 ngày
- Refactor lại cách sinh `profile_code` sang định dạng không thể dự đoán (vd: `HS-XJ7K-92P1`).
- Thay đổi ID các bảng `Student`, `Payment` sang UUID (nếu có thể) hoặc dùng HashId cho các route công khai.

### 3. Trong 30 ngày
- Cài đặt hệ thống Rate Limiting chặt chẽ hơn cho trang tra cứu (hiện tại `throttle:10,1` vẫn hơi nới lỏng cho việc quét dữ liệu tự động).
- Kiểm tra lại toàn bộ logic Commission (Hoa hồng) để đảm bảo không có lỗ hổng "Double Spend" hoặc rút tiền sai trạng thái.

---

## E. Quick Wins
- **Sửa hàm `publicViewBill`**: Thêm một tham số `?token=` vào link xem file, token này là `md5(payment_id + secret_key)`. Chỉ cho xem nếu token khớp. Việc này chỉ mất 15 phút nhưng bịt được lỗ hổng lớn nhất.

---

## F. Nếu thiếu dữ liệu để kết luận
- **Cấu hình Server:** Cần kiểm tra file `.htaccess` hoặc config Nginx/Apache để đảm bảo các file log và thư mục `.git`, `.env` không thể truy cập từ bên ngoài.
- **Google Drive Service Account:** Cần xác nhận quyền hạn của Service Account trên Drive có bị thừa hay không (vd: có quyền xóa hoặc xem toàn bộ Drive thay vì chỉ 1 folder).

---

## G. Security Score

| Nhóm | Điểm | Nhận xét |
| :--- | :---: | :--- |
| **Auth** | **7/10** | Google OAuth tốt, nhưng thiếu Auth cho public assets. |
| **Permission** | **8/10** | Spatie được triển khai đúng bài bản trong Admin. |
| **API/Routes** | **3/10** | Nhiều route công khai bị hổng IDOR nghiêm trọng. |
| **Server** | **7/10** | Laravel chuẩn, cần check kỹ cấu hình web server. |
| **Database** | **8/10** | Cấu trúc ổn, điểm trừ lớn ở Predictable IDs. |
| **Logging** | **9/10** | Audit Log được tích hợp sâu vào Model (Trait). |
| **Business Logic** | **7/10** | Luồng xử lý chặt chẽ nhưng cần chú ý tính duy nhất của dữ liệu. |

---
**Kết luận:** Hệ thống có nền tảng tốt nhưng đang gặp nguy hiểm ở lớp giao tiếp với người dùng công khai. Cần xử lý ngay các lỗ hổng **IDOR** để tránh mất mát dữ liệu.
