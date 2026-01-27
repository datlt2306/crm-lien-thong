---
phase: requirements
title: Yêu cầu & Hiểu bài toán
description: Làm rõ bài toán, thu thập yêu cầu và xác định tiêu chí thành công
---

# Yêu cầu & Hiểu bài toán

> **Ghi chú định hướng kiến trúc:** Toàn bộ hệ thống CRM Tuyển sinh Liên thông được thiết kế theo mô hình **API-first (Backend-only)** trong giai đoạn hiện tại. Tài liệu này đồng thời đóng vai trò **SRS (Software Requirements Specification)** cho hệ thống backend.

## Mô tả bài toán

**Chúng ta đang giải quyết vấn đề gì?**

Hiện tại, quy trình tuyển sinh liên thông đang được vận hành chủ yếu bằng phương pháp thủ công (form đăng ký đơn giản, Excel rời rạc, gọi điện và nhắc hồ sơ thủ công), dẫn đến các vấn đề sau:

-   Sinh viên chỉ điền rất ít thông tin ban đầu, thiếu dữ liệu để đánh giá điều kiện học tập.
-   Gần đến kỳ thi, cô Ly phải gọi điện từng sinh viên để nhắc nộp hồ sơ, kiểm tra thiếu sót và nhập tay rất nhiều thông tin.
-   Việc kiểm tra điều kiện ngành (ngành tốt nghiệp có phù hợp với ngành đăng ký hay không) đang làm thủ công, dễ sai sót.
-   Sinh viên thường xuyên xin dời đợt thi hoặc đổi nguyện vọng, gây khó khăn trong việc theo dõi lịch sử và cập nhật dữ liệu.
-   Dữ liệu hồ sơ, thanh toán, hoa hồng và danh sách gửi Trường bị phân tán ở nhiều file Excel và Google Drive.

**Ai đang bị ảnh hưởng bởi vấn đề này?**

-   Cô Ly: quá tải nhập liệu, kiểm tra hồ sơ và nhắc sinh viên.
-   Cô Vinh: khó nắm tổng quan số lượng hồ sơ, chỉ tiêu theo ngành và theo đợt.
-   Kế toán: đối soát và chi trả hoa hồng thủ công, tiềm ẩn rủi ro nhầm lẫn.
-   Cộng tác viên: không theo dõi được tiến độ hồ sơ sinh viên mình phụ trách.
-   Sinh viên: không biết trạng thái hồ sơ, không rõ mình còn thiếu gì, phụ thuộc hoàn toàn vào việc được gọi nhắc.

**Tình trạng hiện tại / cách làm tạm thời**

-   Form đăng ký chỉ thu thập thông tin tối thiểu.
-   Cô Ly gọi điện, nhắn Zalo để bổ sung thông tin.
-   Kiểm tra điều kiện ngành chủ yếu dựa vào kinh nghiệm cá nhân.
-   Tổng hợp hồ sơ bằng Excel và upload thủ công lên Google Drive.

---

## Mục tiêu & Định hướng

**Chúng ta muốn đạt được điều gì?**

### Mục tiêu chính

-   Xây dựng **SRS cho hệ thống CRM Tuyển sinh Liên thông theo mô hình API-first**.
-   Chuẩn hoá toàn bộ quy trình tuyển sinh liên thông thành các **nghiệp vụ backend rõ ràng, có thể kiểm soát bằng API**.
-   Giảm tối thiểu 50–70% khối lượng nhập tay của cô Ly thông qua phân quyền nhập liệu và checklist số hoá.
-   Cho phép sinh viên tự theo dõi, tự nhập và tự bổ sung hồ sơ thông qua các API portal.
-   Kiểm soát chặt chẽ điều kiện ngành, đợt tuyển sinh và chỉ tiêu ngay từ tầng backend.

### Mục tiêu phụ

-   Tạo nền tảng backend ổn định để dễ dàng phát triển các client sau này (Admin Web, Portal Sinh viên, Mobile App).
-   Minh bạch hoá trạng thái hồ sơ và hoa hồng cộng tác viên thông qua API.
-   Dễ dàng xuất dữ liệu đúng định dạng để gửi Trường thông qua API export.
-   Lưu trữ tập trung hồ sơ và file Excel trên Google Drive, quản lý bằng link và metadata.

### Ngoài phạm vi (không làm trong giai đoạn này)

-   Không xây dựng giao diện người dùng hoàn chỉnh (Admin UI / Portal sinh viên chỉ ở mức tối thiểu để test API nếu cần).
-   Không xây dựng hệ thống học tập (LMS).
-   Không xử lý nghiệp vụ đào tạo sau khi sinh viên đã nhập học.
-   Không thay thế hoàn toàn Google Drive, chỉ tích hợp và liên kết.

---

## Câu chuyện người dùng & Trường hợp sử dụng

**Người dùng sẽ tương tác với hệ thống như thế nào?**

### Sinh viên

-   Là sinh viên, tôi muốn đăng nhập để xem trạng thái hồ sơ để biết mình còn thiếu gì.
-   Là sinh viên, tôi muốn tự nhập và cập nhật thông tin cá nhân để không phải gửi nhiều lần qua Zalo.
-   Là sinh viên, tôi muốn tải lên giấy tờ theo checklist để hồ sơ được duyệt nhanh hơn.

### Cộng tác viên (CTV)

-   Là CTV, tôi muốn tạo lead sinh viên để theo dõi tiến độ tuyển sinh.
-   Là CTV, tôi muốn xem trạng thái hồ sơ sinh viên của mình để biết khi nào cần nhắc.

### Cô Ly (phụ trách hồ sơ)

-   Là người phụ trách hồ sơ, tôi muốn xem checklist để biết sinh viên còn thiếu giấy tờ nào.
-   Là người phụ trách hồ sơ, tôi muốn hệ thống tự kiểm tra điều kiện ngành để giảm rủi ro sai sót.
-   Là người phụ trách hồ sơ, tôi chỉ muốn nhập hoặc sửa thông tin khi thật sự cần để giảm nhập tay.

### Cô Vinh (quản trị)

-   Là quản trị, tôi muốn tạo ngành, đợt và chỉ tiêu để quản lý quy mô tuyển sinh.
-   Là quản trị, tôi muốn xem dashboard tổng hợp để nắm toàn bộ tình hình.

### Kế toán

-   Là kế toán, tôi muốn đối soát phí và hoa hồng để chi trả chính xác và minh bạch.

**Các trường hợp đặc biệt (edge cases)**

-   Sinh viên không đủ điều kiện ngành.
-   Sinh viên xin dời sang đợt sau nhiều lần.
-   Sinh viên nhập sai thông tin cần được chỉnh sửa và lưu lịch sử.

---

## Tiêu chí thành công

**Khi nào thì coi như hệ thống đạt yêu cầu?**

-   Ít nhất 70% hồ sơ được sinh viên tự nhập và tải đủ giấy tờ trước khi cô Ly kiểm tra.
-   Thời gian xử lý trung bình một hồ sơ giảm tối thiểu 50%.
-   Không xảy ra trường hợp gửi hồ sơ sai ngành cho Trường.
-   Kế toán có thể đối soát và chi trả hoa hồng hoàn toàn trong hệ thống.
-   Xuất được file Excel đúng định dạng Trường chỉ với một thao tác.

---

## Ràng buộc & Giả định

**Những giới hạn cần tuân thủ trong SRS**

### Ràng buộc kỹ thuật

-   Hệ thống được thiết kế và triển khai theo mô hình **Backend-only / API-first**.
-   Toàn bộ nghiệp vụ phải được thể hiện rõ ở tầng API (không phụ thuộc UI).
-   API có thể là REST hoặc GraphQL, nhưng phải thống nhất và có tài liệu.
-   Bắt buộc có:

    -   Phân quyền theo vai trò
    -   Workflow trạng thái hồ sơ
    -   Kiểm soát điều kiện ngành
    -   Log thay đổi dữ liệu (audit log)
    -   Upload tài liệu và quản lý file
    -   Tích hợp Google Drive
    -   Xuất Excel theo template tuyển sinh

### Ràng buộc nghiệp vụ

-   Quy trình thủ công hiện tại vẫn phải chạy song song trong giai đoạn đầu.
-   Không được làm gián đoạn hoạt động tuyển sinh đang diễn ra.
-   Cô Ly vẫn là người kiểm soát cuối cùng trước khi gửi hồ sơ cho Trường.

### Ràng buộc thời gian / ngân sách

-   Ưu tiên triển khai theo từng module backend (MVP → mở rộng).
-   Ngân sách và nguồn lực IT có hạn, cần thiết kế đủ dùng nhưng không thừa.

### Giả định

-   Sinh viên có smartphone và có thể tương tác với API thông qua portal/web/app.
-   Cô Ly, CTV, Kế toán sẽ sử dụng hệ thống thông qua các client được xây dựng sau.

---

**Những giới hạn cần tuân thủ**

### Ràng buộc kỹ thuật

-   Giai đoạn hiện tại hệ thống được triển khai theo mô hình **API-first / Backend-only**.
-   Hệ thống chỉ cần xây dựng các API nghiệp vụ (REST hoặc GraphQL), chưa yêu cầu xây dựng giao diện người dùng hoàn chỉnh.
-   API phải được thiết kế đủ tổng quát để có thể tích hợp với nhiều client trong tương lai (Admin web, Portal sinh viên, Mobile app).
-   Dù chỉ triển khai API, hệ thống vẫn bắt buộc có đầy đủ: phân quyền theo vai trò, workflow trạng thái hồ sơ, kiểm soát nghiệp vụ, log thay đổi dữ liệu.
-   Cần hỗ trợ upload file, liên kết Google Drive và xuất Excel theo template tuyển sinh.

### Ràng buộc nghiệp vụ

-   Quy trình thủ công hiện tại vẫn phải chạy song song trong giai đoạn đầu.
-   Không được làm gián đoạn hoạt động tuyển sinh đang diễn ra.

### Ràng buộc thời gian / ngân sách

-   Ưu tiên triển khai từng phần (MVP → mở rộng).
-   Ngân sách và nguồn lực IT có hạn.

### Giả định

-   Sinh viên có smartphone và có thể tự tải hồ sơ.
-   Cô Ly và CTV sẵn sàng chuyển dần sang CRM.

---

## Bổ sung: Yêu cầu dữ liệu hồ sơ sinh viên (Data Requirements)

Hệ thống CRM phải hỗ trợ đầy đủ các trường dữ liệu hồ sơ sinh viên theo đúng biểu mẫu tuyển sinh hiện hành, đồng thời tối ưu hoá việc phân quyền nhập liệu nhằm giảm tải nhập tay cho cô Ly.

### 1. Nguyên tắc thiết kế dữ liệu

-   Trường dữ liệu nào sinh viên có thể tự nhập và tự cung cấp minh chứng thì cho phép sinh viên nhập trực tiếp.
-   Cô Ly chỉ kiểm tra, duyệt, chỉnh sửa các trường hợp sai hoặc thiếu (ngoại lệ).
-   Các trường có thể suy ra, tính toán hoặc tra cứu thì hệ thống tự động sinh.
-   Mọi chỉnh sửa đều phải được lưu lịch sử (ai sửa, thời điểm, nội dung thay đổi).

---

### 2. Nhóm trường hệ thống tự sinh

-   STT
-   Ngày tháng (ngày tạo / ngày cập nhật hồ sơ)
-   Trạng thái hồ sơ
-   Tình trạng hồ sơ
-   Phiếu tuyển sinh
-   Lệ phí (tự động theo Hệ ĐKLT)
-   Hình thức tuyển sinh
-   KV ưu tiên (tính theo địa chỉ hoặc trường THPT nếu có quy tắc)

---

### 3. Nhóm trường sinh viên tự nhập

#### 3.1 Thông tin cá nhân

-   Họ và tên
-   Ngày sinh
-   Nơi sinh
-   Hộ khẩu thường trú
-   Số điện thoại
-   Dân tộc
-   Giới tính
-   CCCD
-   Ngày cấp CCCD
-   Nơi cấp CCCD

#### 3.2 Thông tin THPT

-   Tên trường THPT
-   Mã trường
-   Tên tỉnh/TP
-   Mã tỉnh
-   Tên quận/huyện
-   Mã quận/huyện
-   Năm tốt nghiệp THPT
-   Học lực cả năm
-   Hạnh kiểm

#### 3.3 Thông tin văn bằng Cao đẳng / Trung cấp

-   Trường tốt nghiệp CĐ
-   Ngành tốt nghiệp CĐ
-   Xếp loại
-   Hệ đào tạo tốt nghiệp
-   Năm tốt nghiệp
-   Số hiệu bằng TN CĐ
-   Số vào sổ cấp bằng TN CĐ
-   Ngày ký bằng TN CĐ
-   Người ký bằng TN CĐ
-   Thông tin Trung cấp (nếu có)

#### 3.4 Tải lên giấy tờ minh chứng

-   Bằng TN CĐ (bản sao / bản scan)
-   Bảng điểm CĐ
-   Bằng TN THPT
-   Giấy khai sinh
-   CCCD (mặt trước / sau)
-   Ảnh cá nhân
-   Giấy khám sức khoẻ

---

### 4. Nhóm trường cô Ly hoàn thiện và duyệt

-   Kiểm tra điều kiện ngành (Đạt / Không đạt / Cần xem xét)
-   Ngành đăng ký liên thông
-   Trường đăng ký liên thông
-   Hệ đăng ký liên thông
-   Đợt đăng ký liên thông
-   Ghi chú nghiệp vụ

---

### 5. Quy tắc khoá – duyệt – chỉnh sửa dữ liệu

-   Sinh viên được chỉnh sửa dữ liệu trước khi nộp hồ sơ.
-   Sau khi nộp, các trường quan trọng sẽ bị khoá và chỉ được sửa khi gửi yêu cầu.
-   Cô Ly có quyền chỉnh sửa khi duyệt hồ sơ, bắt buộc nhập lý do chỉnh sửa.
-   Hệ thống lưu song song giá trị sinh viên nhập và giá trị đã duyệt.

---

### 6. Yêu cầu tích hợp Google Drive & Excel

-   Mỗi đợt tuyển sinh có một thư mục Google Drive riêng.
-   Mỗi sinh viên có một thư mục con chứa toàn bộ giấy tờ.
-   CRM phải xuất được file Excel đúng template tuyển sinh hiện hành.
-   Link Google Drive và file Excel được lưu trực tiếp trong hồ sơ sinh viên.

---

## Câu hỏi & Vấn đề cần làm rõ (Open Items cho SRS)

-   Danh sách mapping ngành đầu vào – đầu ra chính thức từ Trường để đưa vào rule backend.

-   Template Excel cuối cùng Trường yêu cầu (có thay đổi theo từng đợt hay không).

-   Những trường dữ liệu nào sinh viên được phép chỉnh sửa sau khi đã nộp hồ sơ (rule khoá field).

-   Cách xử lý các trường hợp ngoại lệ: ngành gần, bằng đặc thù, chuyển đợt nhiều lần.

-   Thứ tự triển khai các module API:

    -   Hồ sơ sinh viên
    -   Quản lý ngành / hệ / đợt - chỉ tiêu
    -   Thanh toán & hoa hồng
    -   Export & Google Drive integration

-   Danh sách mapping ngành đầu vào – đầu ra chính thức từ Trường.

-   Template Excel cuối cùng Trường yêu cầu (có thay đổi theo từng đợt hay không).

-   Các trường dữ liệu nào sinh viên được phép chỉnh sửa sau khi đã nộp hồ sơ.

-   Quy định xử lý các trường hợp ngoại lệ (bằng liên thông đặc thù, ngành gần).

-   Lộ trình triển khai: ưu tiên module hồ sơ sinh viên, quản lý ngành hay kế toán trước.

# Danh sách feature

## Sinh viên

-   `feature-student-register-profile`: Đăng ký / khởi tạo hồ sơ sinh viên.
-   `feature-student-edit-profile`: Xem & cập nhật thông tin cá nhân, THPT, CĐ/TC.
-   `feature-student-upload-documents`: Upload & quản lý giấy tờ minh chứng.
-   `feature-student-submit-application`: Nộp hồ sơ tuyển sinh (Draft → Submitted).
-   `feature-student-track-application-status`: Theo dõi trạng thái hồ sơ & checklist giấy tờ.
-   `feature-student-payment-status`: Xem trạng thái và thông tin thanh toán.
-   `feature-student-change-intake-or-major`: Gửi yêu cầu đổi đợt / đổi nguyện vọng.
-   `feature-student-data-lock-rules`: Rule khoá – duyệt – chỉnh sửa dữ liệu phía sinh viên.

## Cộng tác viên

-   `feature-ctv-create-student-lead`: CTV tạo lead / hồ sơ sinh viên.
-   `feature-ctv-student-pipeline`: Xem danh sách & pipeline sinh viên trong nhánh.
-   `feature-ctv-track-student-status`: Theo dõi trạng thái hồ sơ & thanh toán sinh viên.
-   `feature-ctv-support-student-payment`: Hỗ trợ sinh viên nộp lệ phí & xác nhận ban đầu.
-   `feature-ctv-commission-overview`: Xem tổng quan hoa hồng CTV.
-   `feature-ctv-wallet-transactions`: Ví hoa hồng & lịch sử giao dịch.
-   `feature-ctv-downline-management`: Quản lý downline & doanh thu từ nhánh.
-   `feature-ctv-access-control`: Quyền truy cập dữ liệu theo nhánh CTV.

## Cán bộ hồ sơ (Cô Ly)

-   `feature-document-officer-student-list`: Danh sách & lọc hồ sơ sinh viên cần xử lý.
-   `feature-document-officer-view-application-detail`: Xem chi tiết hồ sơ & checklist giấy tờ.
-   `feature-document-officer-major-eligibility`: Đánh giá điều kiện ngành & quyết định hồ sơ.
-   `feature-document-officer-exception-edit`: Chỉnh sửa ngoại lệ & lưu lịch sử thay đổi.
-   `feature-document-officer-request-more-documents`: Yêu cầu bổ sung giấy tờ & ghi chú.
-   `feature-document-officer-verify-payment`: Phối hợp xác nhận thanh toán (verify payment).
-   `feature-document-officer-export-to-school`: Chuẩn bị & export danh sách gửi Trường.
-   `feature-document-officer-audit-log`: Audit log cho mọi thao tác trên hồ sơ.

## Kế toán

-   `feature-accountant-payment-list`: Danh sách & lọc các thanh toán tuyển sinh.
-   `feature-accountant-verify-payment`: Đối soát & xác nhận thanh toán.
-   `feature-accountant-upload-receipt`: Upload & quản lý phiếu thu / chứng từ.
-   `feature-accountant-commission-payout`: Đối soát & chi trả hoa hồng CTV.
-   `feature-accountant-wallet-adjustments`: Giao dịch ví hoa hồng & cập nhật số dư.
-   `feature-accountant-financial-reports`: Báo cáo doanh thu & hoa hồng (export Excel).
-   `feature-accountant-audit-log`: Audit log cho các thao tác tài chính.

## Quản lý tổ chức (Cô Vinh)

-   `feature-org-owner-manage-organization`: Xem & cập nhật thông tin tổ chức.
-   `feature-org-owner-manage-members`: Quản lý user nội bộ & CTV trong tổ chức.
-   `feature-org-owner-program-and-major-config`: Cấu hình ngành & chương trình.
-   `feature-org-owner-intake-and-quota-config`: Cấu hình đợt tuyển sinh & quota.
-   `feature-org-owner-commission-policy-config`: Cấu hình chính sách hoa hồng.
-   `feature-org-owner-dashboard`: Dashboard & báo cáo tổng quan cho tổ chức.
-   `feature-org-owner-operational-rules`: Thiết lập rule vận hành (khoá field, cảnh báo quota, v.v.).
-   `feature-org-owner-config-audit-log`: Audit log cho mọi thay đổi cấu hình tổ chức.

---

# Danh sách API cần thiết

> **Lưu ý:** Danh sách này được tổ chức theo các features và actors. Format API theo chuẩn REST. Tất cả API đều yêu cầu authentication và authorization theo vai trò.

## Authentication & Authorization

-   `POST /api/auth/login`: Đăng nhập (trả về access token)
-   `POST /api/auth/logout`: Đăng xuất
-   `POST /api/auth/refresh`: Refresh access token
-   `GET /api/auth/me`: Lấy thông tin user hiện tại
-   `GET /api/auth/permissions`: Lấy danh sách permissions của user

## API cho Sinh viên

### Quản lý hồ sơ

-   `POST /api/students/register`: Đăng ký / khởi tạo hồ sơ sinh viên
-   `GET /api/students/me`: Xem thông tin hồ sơ của mình
-   `GET /api/students/me/profile`: Xem chi tiết thông tin cá nhân
-   `PATCH /api/students/me/profile`: Cập nhật thông tin cá nhân (theo rule khoá field)
-   `GET /api/students/me/education/high-school`: Xem thông tin THPT
-   `PATCH /api/students/me/education/high-school`: Cập nhật thông tin THPT
-   `GET /api/students/me/education/college`: Xem thông tin CĐ/TC
-   `PATCH /api/students/me/education/college`: Cập nhật thông tin CĐ/TC

### Upload giấy tờ

-   `GET /api/students/me/documents`: Xem danh sách giấy tờ đã upload
-   `GET /api/students/me/documents/checklist`: Xem checklist giấy tờ cần thiết
-   `POST /api/students/me/documents`: Upload giấy tờ mới
-   `GET /api/students/me/documents/{id}`: Xem chi tiết giấy tờ
-   `PUT /api/students/me/documents/{id}`: Thay thế giấy tờ
-   `DELETE /api/students/me/documents/{id}`: Xóa giấy tờ (chỉ khi chưa nộp hồ sơ)

### Nộp hồ sơ và theo dõi

-   `POST /api/students/me/application/submit`: Nộp hồ sơ (Draft → Submitted)
-   `GET /api/students/me/application`: Xem thông tin hồ sơ đăng ký
-   `GET /api/students/me/application/status`: Xem trạng thái hồ sơ
-   `GET /api/students/me/application/checklist`: Xem checklist giấy tờ và trạng thái

### Thanh toán

-   `GET /api/students/me/payment`: Xem thông tin thanh toán
-   `GET /api/students/me/payment/status`: Xem trạng thái thanh toán

### Yêu cầu thay đổi

-   `POST /api/students/me/requests/change-intake`: Gửi yêu cầu đổi đợt
-   `POST /api/students/me/requests/change-major`: Gửi yêu cầu đổi nguyện vọng
-   `GET /api/students/me/requests`: Xem danh sách yêu cầu đã gửi
-   `GET /api/students/me/requests/{id}`: Xem chi tiết yêu cầu

## API cho Cộng tác viên (CTV)

### Quản lý Lead

-   `GET /api/leads`: Xem danh sách leads của mình (có filter, pagination)
-   `POST /api/leads`: Tạo lead sinh viên mới
-   `GET /api/leads/{id}`: Xem chi tiết lead
-   `PATCH /api/leads/{id}`: Cập nhật thông tin lead
-   `DELETE /api/leads/{id}`: Xóa lead (chỉ khi chưa chuyển thành Student)
-   `POST /api/leads/{id}/activities`: Thêm hoạt động/lịch sử cho lead
-   `GET /api/leads/{id}/activities`: Xem lịch sử hoạt động của lead

### Pipeline và theo dõi sinh viên

-   `GET /api/recruiters/me/students`: Xem danh sách sinh viên phụ trách
-   `GET /api/recruiters/me/students/{id}`: Xem chi tiết sinh viên
-   `GET /api/recruiters/me/students/{id}/status`: Xem trạng thái hồ sơ sinh viên
-   `GET /api/recruiters/me/students/{id}/payment`: Xem thông tin thanh toán của sinh viên
-   `GET /api/recruiters/me/pipeline`: Xem pipeline sinh viên (theo trạng thái)

### Hoa hồng

-   `GET /api/recruiters/me/commissions`: Xem danh sách hoa hồng
-   `GET /api/recruiters/me/commissions/summary`: Xem tổng quan hoa hồng
-   `GET /api/recruiters/me/commissions/{id}`: Xem chi tiết hoa hồng
-   `GET /api/recruiters/me/wallet`: Xem ví hoa hồng và số dư
-   `GET /api/recruiters/me/wallet/transactions`: Xem lịch sử giao dịch ví

### Dashboard

-   `GET /api/recruiters/me/dashboard`: Xem dashboard tổng hợp (số leads, tỷ lệ chuyển đổi, v.v.)

## API cho Cán bộ hồ sơ (Cô Ly)

### Danh sách và lọc hồ sơ

-   `GET /api/students`: Xem danh sách hồ sơ sinh viên (có filter, search, pagination)
-   `GET /api/students/{id}`: Xem chi tiết hồ sơ sinh viên
-   `GET /api/students/{id}/checklist`: Xem checklist giấy tờ của sinh viên

### Đánh giá và duyệt hồ sơ

-   `POST /api/students/{id}/evaluate-major-eligibility`: Đánh giá điều kiện ngành
-   `PATCH /api/students/{id}/application`: Cập nhật thông tin hồ sơ (ngành, đợt, hệ)
-   `POST /api/students/{id}/application/approve`: Duyệt hồ sơ
-   `POST /api/students/{id}/application/reject`: Từ chối hồ sơ (kèm lý do)
-   `POST /api/students/{id}/application/request-more-documents`: Yêu cầu bổ sung giấy tờ

### Chỉnh sửa ngoại lệ

-   `PATCH /api/students/{id}/profile`: Chỉnh sửa thông tin sinh viên (bắt buộc nhập lý do)
-   `GET /api/students/{id}/audit-logs`: Xem audit log của hồ sơ

### Xác nhận thanh toán

-   `GET /api/payments`: Xem danh sách thanh toán
-   `GET /api/payments/{id}`: Xem chi tiết thanh toán
-   `POST /api/payments/{id}/verify`: Xác nhận thanh toán (phối hợp với kế toán)

### Export

-   `GET /api/students/export`: Export danh sách hồ sơ (Excel format)
-   `POST /api/students/export-to-school`: Export danh sách gửi Trường

## API cho Kế toán

### Quản lý thanh toán

-   `GET /api/payments`: Xem danh sách thanh toán (có filter, search, pagination)
-   `GET /api/payments/{id}`: Xem chi tiết thanh toán
-   `POST /api/payments/{id}/verify`: Xác nhận thanh toán
-   `POST /api/payments/{id}/transactions`: Thêm giao dịch thanh toán
-   `GET /api/payments/{id}/transactions`: Xem danh sách giao dịch
-   `POST /api/payments/{id}/receipts`: Upload phiếu thu/chứng từ
-   `GET /api/payments/{id}/receipts`: Xem phiếu thu/chứng từ

### Quản lý hoa hồng

-   `GET /api/commissions`: Xem danh sách hoa hồng (có filter)
-   `GET /api/commissions/{id}`: Xem chi tiết hoa hồng
-   `POST /api/commissions/{id}/approve`: Duyệt hoa hồng
-   `POST /api/commissions/{id}/payout`: Đánh dấu đã chi trả hoa hồng
-   `GET /api/commissions/payouts`: Xem danh sách hoa hồng cần chi trả

### Ví hoa hồng

-   `GET /api/recruiters/{id}/wallet`: Xem ví hoa hồng của CTV
-   `POST /api/recruiters/{id}/wallet/adjustments`: Thêm giao dịch điều chỉnh ví
-   `GET /api/recruiters/{id}/wallet/transactions`: Xem lịch sử giao dịch ví

### Báo cáo tài chính

-   `GET /api/reports/financial`: Xem báo cáo tài chính (thanh toán + hoa hồng)
-   `GET /api/reports/financial/export`: Export báo cáo tài chính (Excel)
-   `GET /api/reports/commissions`: Xem báo cáo hoa hồng
-   `GET /api/reports/commissions/export`: Export báo cáo hoa hồng (Excel)

### Audit log

-   `GET /api/audit-logs/financial`: Xem audit log các thao tác tài chính

## API cho Quản lý tổ chức (Cô Vinh)

### Quản lý tổ chức

-   `GET /api/organization`: Xem thông tin tổ chức
-   `PATCH /api/organization`: Cập nhật thông tin tổ chức

### Quản lý thành viên

-   `GET /api/organization/members`: Xem danh sách thành viên (staff, CTV)
-   `POST /api/organization/members`: Thêm thành viên mới
-   `GET /api/organization/members/{id}`: Xem chi tiết thành viên
-   `PATCH /api/organization/members/{id}`: Cập nhật thông tin thành viên
-   `DELETE /api/organization/members/{id}`: Xóa thành viên
-   `POST /api/organization/members/{id}/roles`: Gán vai trò cho thành viên

### Quản lý ngành và chương trình

-   `GET /api/majors`: Xem danh sách ngành
-   `POST /api/majors`: Tạo ngành mới
-   `GET /api/majors/{id}`: Xem chi tiết ngành
-   `PATCH /api/majors/{id}`: Cập nhật ngành
-   `DELETE /api/majors/{id}`: Xóa ngành (chỉ khi không có sinh viên đăng ký)
-   `GET /api/majors/eligibility-mappings`: Xem danh sách mapping điều kiện ngành
-   `POST /api/majors/eligibility-mappings`: Tạo mapping điều kiện ngành
-   `PATCH /api/majors/eligibility-mappings/{id}`: Cập nhật mapping
-   `DELETE /api/majors/eligibility-mappings/{id}`: Xóa mapping

### Quản lý đợt tuyển sinh và chỉ tiêu

-   `GET /api/intakes`: Xem danh sách đợt tuyển sinh
-   `POST /api/intakes`: Tạo đợt tuyển sinh mới
-   `GET /api/intakes/{id}`: Xem chi tiết đợt
-   `PATCH /api/intakes/{id}`: Cập nhật đợt
-   `DELETE /api/intakes/{id}`: Xóa đợt (chỉ khi không có sinh viên đăng ký)
-   `GET /api/intakes/{id}/quotas`: Xem danh sách chỉ tiêu của đợt
-   `POST /api/intakes/{id}/quotas`: Tạo chỉ tiêu mới
-   `GET /api/quotas/{id}`: Xem chi tiết chỉ tiêu
-   `PATCH /api/quotas/{id}`: Cập nhật chỉ tiêu
-   `DELETE /api/quotas/{id}`: Xóa chỉ tiêu

### Cấu hình chính sách hoa hồng

-   `GET /api/commission-rules`: Xem danh sách quy tắc tính hoa hồng
-   `POST /api/commission-rules`: Tạo quy tắc mới
-   `GET /api/commission-rules/{id}`: Xem chi tiết quy tắc
-   `PATCH /api/commission-rules/{id}`: Cập nhật quy tắc
-   `DELETE /api/commission-rules/{id}`: Xóa quy tắc

### Dashboard và báo cáo

-   `GET /api/organization/dashboard`: Xem dashboard tổng quan tổ chức
-   `GET /api/reports/enrollment`: Xem báo cáo tuyển sinh
-   `GET /api/reports/enrollment/export`: Export báo cáo tuyển sinh (Excel)

### Quy tắc vận hành

-   `GET /api/organization/rules`: Xem danh sách quy tắc vận hành
-   `POST /api/organization/rules`: Tạo quy tắc mới
-   `PATCH /api/organization/rules/{id}`: Cập nhật quy tắc
-   `DELETE /api/organization/rules/{id}`: Xóa quy tắc

### Audit log

-   `GET /api/audit-logs/organization`: Xem audit log các thay đổi cấu hình tổ chức

## API chung (Common APIs)

### Upload files

-   `POST /api/files/upload`: Upload file (giấy tờ, chứng từ, v.v.)
-   `GET /api/files/{id}`: Xem/download file
-   `DELETE /api/files/{id}`: Xóa file

### Tích hợp Google Drive

-   `GET /api/google-drive/folders`: Xem danh sách thư mục Google Drive
-   `POST /api/google-drive/folders`: Tạo thư mục mới
-   `POST /api/google-drive/sync`: Đồng bộ với Google Drive

### Export/Import

-   `GET /api/exports/{id}/status`: Xem trạng thái export
-   `GET /api/exports/{id}/download`: Download file export

---

**Lưu ý:**

-   Tất cả API đều sử dụng format JSON cho request/response
-   Tất cả API đều yêu cầu authentication (Bearer token)
-   Phân quyền được kiểm tra ở từng endpoint
-   API hỗ trợ pagination (page, per_page), filtering, và sorting
-   API versioning có thể được thêm vào path: `/api/v1/...`
