---
phase: requirements
role: sinh_vien
title: SRS – Vai trò Sinh viên
description: Đặc tả yêu cầu nghiệp vụ và hành vi hệ thống cho vai trò Sinh viên trong CRM Tuyển sinh Liên thông
---

## 1. Giới thiệu & Bối cảnh

### 1.1. Mô tả vai trò

-   **Sinh viên** là người đăng ký tham gia chương trình tuyển sinh liên thông thông qua các kênh (portal/web/app, link giới thiệu CTV, form đăng ký, v.v.).
-   Sinh viên là chủ thể của **hồ sơ tuyển sinh**, chịu trách nhiệm cung cấp thông tin cá nhân và tải lên đầy đủ giấy tờ minh chứng theo checklist.
-   Sinh viên không trực tiếp thao tác trên các bảng quản trị (Organization, Quota, Payment configuration, v.v.) nhưng **mọi thao tác nhập liệu** của sinh viên sẽ được sử dụng bởi các vai trò khác (Cán bộ hồ sơ, Quản lý tổ chức, Kế toán, Cộng tác viên).

### 1.2. Phạm vi SRS cho vai trò Sinh viên

-   Mô tả các **API / nghiệp vụ backend liên quan trực tiếp** đến việc sinh viên:
    -   Khởi tạo và quản lý hồ sơ cá nhân.
    -   Cập nhật thông tin, tải lên giấy tờ minh chứng.
    -   Theo dõi trạng thái hồ sơ, thanh toán.
    -   Tương tác gián tiếp với CTV, Cán bộ hồ sơ, Kế toán qua thông báo và trạng thái.
-   Không mô tả chi tiết UI, chỉ tập trung vào **dữ liệu, luồng nghiệp vụ và quyền truy cập**.

## 2. Mục tiêu theo vai trò

-   Sinh viên có thể **tự nhập, tự cập nhật và tự bổ sung** phần lớn dữ liệu hồ sơ, giảm phụ thuộc vào việc nhắn tin/gọi điện thủ công.
-   Sinh viên luôn biết:
    -   **Trạng thái hiện tại** của hồ sơ (đang nhập, đã nộp, thiếu giấy tờ, đủ điều kiện, không đủ điều kiện, v.v.).
    -   **Những giấy tờ còn thiếu** theo checklist.
    -   **Trạng thái thanh toán** (chưa thanh toán, đã nộp, đã xác nhận, bị từ chối, v.v.).
-   Mọi thay đổi từ phía sinh viên đều được:
    -   Ghi log (ai, lúc nào, thay đổi gì).
    -   Tuân thủ quy tắc **khoá – duyệt – chỉnh sửa** của hệ thống.

## 3. Use Case chính cho Sinh viên

### 3.1. Đăng ký / Khởi tạo hồ sơ

-   **Mô tả**:
    -   Sinh viên truy cập portal / form đăng ký để bắt đầu tạo hồ sơ tuyển sinh.
-   **Yêu cầu hệ thống**:
    -   Cung cấp API để khởi tạo bản ghi `Student` với các thông tin tối thiểu:
        -   Thông tin liên hệ cơ bản (họ tên, số điện thoại, email).
        -   Thông tin nhận diện (CCCD hoặc trường dữ liệu tương đương).
    -   Gắn kết hồ sơ với:
        -   **CTV** (nếu sinh viên đi qua link giới thiệu hoặc được CTV nhập giúp).
        -   **Đợt tuyển sinh / ngành đăng ký dự kiến** (nếu đã có).
    -   Sinh viên sau đó có thể đăng nhập hoặc sử dụng mã tra cứu để tiếp tục hoàn thiện hồ sơ.

### 3.2. Xem & cập nhật thông tin cá nhân

-   **Mô tả**:
    -   Sinh viên xem lại và chỉnh sửa các thông tin cá nhân trước khi chính thức nộp hồ sơ.
-   **Dữ liệu**:
    -   Nhóm trường theo `docs/srs.md` (mục 3.1 Thông tin cá nhân, 3.2 Thông tin THPT, 3.3 Thông tin CĐ/TC).
-   **Yêu cầu hệ thống**:
    -   API cho phép sinh viên:
        -   Lấy chi tiết hồ sơ cá nhân của chính mình (không được xem hồ sơ người khác).
        -   Cập nhật các trường được phép chỉnh sửa **trước khi nộp hồ sơ**.
    -   Mọi thay đổi:
        -   Ghi lại lịch sử (old value, new value, thời gian, user là sinh viên).
    -   Sau khi hồ sơ ở trạng thái **đã nộp**, một số trường sẽ:
        -   Bị khoá với sinh viên.
        -   Chỉ có thể thay đổi thông qua luồng yêu cầu hỗ trợ (Cán bộ hồ sơ chỉnh sửa, có lý do).

### 3.3. Tải lên giấy tờ minh chứng

-   **Mô tả**:
    -   Sinh viên tải lên các file scan/bản chụp giấy tờ: bằng tốt nghiệp, bảng điểm, CCCD, giấy tờ khác.
-   **Dữ liệu theo checklist** (tham chiếu `docs/srs.md`, mục 3.4):
    -   Bằng TN CĐ, Bảng điểm CĐ, Bằng TN THPT, Giấy khai sinh, CCCD, Ảnh cá nhân, Giấy khám sức khoẻ, v.v.
-   **Yêu cầu hệ thống**:
    -   API upload file:
        -   Gắn mỗi file với **hồ sơ sinh viên** tương ứng (model `StudentDocument` hoặc tương đương).
        -   Lưu metadata: loại giấy tờ, link Google Drive, thời gian upload, người upload (sinh viên).
    -   Tích hợp Google Drive:
        -   Mỗi sinh viên sẽ có một thư mục riêng trong thư mục đợt tuyển sinh:
            -   `/DriveFolder/{Intake}/{Student}/...`
        -   Link Google Drive được lưu trong hồ sơ sinh viên để cán bộ hồ sơ và quản lý truy cập.
    -   Kiểm soát kích thước file, loại file (PDF/JPEG/PNG, v.v.).

### 3.4. Nộp hồ sơ tuyển sinh

-   **Mô tả**:
    -   Sau khi hoàn thiện thông tin và giấy tờ ở mức chấp nhận được, sinh viên thực hiện thao tác “Nộp hồ sơ”.
-   **Yêu cầu hệ thống**:
    -   API chuyển trạng thái hồ sơ từ **Draft/Đang nhập** sang **Submitted/Đã nộp**.
    -   Khi nộp:
        -   Khoá các trường quan trọng (theo rule ở phần 5 – Quy tắc khoá).
        -   Ghi lại thời điểm nộp hồ sơ.
        -   Thông báo (notification) tới CTV phụ trách (nếu có) và Cán bộ hồ sơ.
    -   Nếu thiếu giấy tờ bắt buộc:
        -   Cho phép nộp hồ sơ nhưng đánh dấu trạng thái là **Thiếu giấy tờ**, để Cán bộ hồ sơ xem và yêu cầu bổ sung.

### 3.5. Theo dõi trạng thái hồ sơ

-   **Mô tả**:
    -   Sinh viên xem trạng thái xử lý hồ sơ ở mọi thời điểm.
-   **Các trạng thái chính (gợi ý)**:
    -   Đang nhập, Đã nộp, Thiếu giấy tờ, Đủ giấy tờ, Đủ điều kiện, Không đủ điều kiện, Đã gửi Trường, v.v.
-   **Yêu cầu hệ thống**:
    -   API trả về:
        -   Trạng thái hiện tại của hồ sơ.
        -   Lý do nếu hồ sơ **không đủ điều kiện** hoặc bị yêu cầu bổ sung.
        -   Checklist giấy tờ: đã nộp / chưa nộp.
    -   Hỗ trợ log lịch sử trạng thái:
        -   Ai thay đổi (sinh viên, CTV, Cán bộ hồ sơ, hệ thống).
        -   Thời điểm thay đổi.

### 3.6. Theo dõi và hỗ trợ thanh toán

-   **Mô tả**:
    -   Sinh viên nộp lệ phí tuyển sinh theo hướng dẫn, có thể thông qua CTV hoặc chuyển khoản trực tiếp.
-   **Yêu cầu hệ thống (liên quan tới Payment)**:
    -   API cho phép sinh viên:
        -   Xem số tiền phải nộp và trạng thái thanh toán (chưa nộp, đã nộp, đã xác nhận, bị từ chối).
        -   Xem hướng dẫn thanh toán (không nhất thiết từ backend, nhưng backend cần lưu được metadata).
    -   Khi thanh toán:
        -   Thông tin thanh toán được ghi nhận vào model `Payment`.
        -   Việc xác nhận số tiền, upload phiếu thu do **CTV / Kế toán / Cán bộ hồ sơ** thực hiện (theo `PaymentPolicy`), sinh viên chỉ quan sát được kết quả.

### 3.7. Xin đổi đợt / đổi nguyện vọng (nếu được hỗ trợ)

-   **Mô tả**:
    -   Sinh viên có thể yêu cầu chuyển sang đợt sau hoặc đổi ngành/nguyện vọng.
-   **Yêu cầu hệ thống**:
    -   API cho phép sinh viên:
        -   Tạo yêu cầu đổi đợt / đổi ngành.
        -   Ghi lý do, thời điểm tạo yêu cầu.
    -   Yêu cầu này sẽ được xử lý bởi Cán bộ hồ sơ hoặc Quản lý tổ chức, có log chấp nhận / từ chối.

## 4. Quy tắc quyền hạn & bảo mật cho Sinh viên

-   Sinh viên **chỉ được truy cập**:
    -   Hồ sơ của chính mình.
    -   Các tài nguyên liên quan trực tiếp tới hồ sơ đó (document, payment, trạng thái).
-   Sinh viên **không được**:
    -   Xem danh sách tất cả sinh viên.
    -   Truy cập thông tin hoa hồng CTV, thông tin nội bộ của tổ chức.
-   Mọi API cho sinh viên cần:
    -   Xác thực (token/phiên đăng nhập hoặc mã tra cứu một lần có giới hạn).
    -   Kiểm tra quyền theo ID sinh viên gắn với tài khoản.

## 5. Quy tắc khoá – duyệt – chỉnh sửa dữ liệu (theo góc nhìn Sinh viên)

-   Trước khi nộp hồ sơ:
    -   Sinh viên có thể sửa **hầu hết** các trường trong nhóm:
        -   Thông tin cá nhân, THPT, CĐ/TC.
        -   Upload / xoá / thay thế giấy tờ minh chứng.
-   Sau khi nộp hồ sơ:
    -   Một số trường quan trọng (họ tên, CCCD, ngành đăng ký, v.v.) bị khoá khỏi chỉnh sửa trực tiếp bởi sinh viên.
    -   Sinh viên vẫn có thể:
        -   Bổ sung thêm giấy tờ theo yêu cầu.
        -   Cập nhật một số trường ít nhạy cảm (ví dụ: số điện thoại liên lạc) nếu rule cho phép.
    -   Nếu cần chỉnh sửa trường bị khoá:
        -   Sinh viên phải tạo yêu cầu, Cán bộ hồ sơ duyệt và chỉnh sửa, hệ thống lưu log song song giá trị **sinh viên nhập ban đầu** và **giá trị đã duyệt**.

## 6. Dữ liệu & tích hợp liên quan đến Sinh viên

### 6.1. Dữ liệu chính mà Sinh viên thao tác

-   Bản ghi **Student**:
    -   Thông tin cá nhân.
    -   Thông tin học tập (THPT, CĐ/TC).
    -   Thông tin ngành / đợt đăng ký (ở mức cho phép, có thể chỉ chọn từ danh sách hợp lệ).
-   Bản ghi **StudentDocument** (hoặc tương đương):
    -   Loại giấy tờ, link Google Drive, trạng thái duyệt giấy tờ.
-   Bản ghi **Payment** (quyền xem):
    -   Số tiền, trạng thái, ghi chú từ CTV/Kế toán/Cán bộ hồ sơ.

### 6.2. Tích hợp Google Drive & Excel

-   Với vai trò sinh viên:
    -   Không thao tác trực tiếp trên Google Drive, nhưng:
        -   Mọi file upload sẽ được lưu vào thư mục Drive tương ứng với hồ sơ của sinh viên.
    -   Sinh viên không trực tiếp xuất Excel, nhưng:
        -   Dữ liệu sinh viên nhập là nguồn để hệ thống sinh file Excel gửi Trường.

## 7. Tiêu chí thành công theo vai trò Sinh viên

-   Ít nhất **70% hồ sơ** được sinh viên tự nhập và tự tải đủ giấy tờ trước khi Cán bộ hồ sơ kiểm tra.
-   Sinh viên có thể **tự tra cứu** trạng thái hồ sơ mà không phải gọi điện hỏi.
-   Số lần phải chỉnh sửa dữ liệu do nhập sai sau khi nộp hồ sơ giảm rõ rệt (nhờ rule khoá field và checklist rõ ràng).
-   Sinh viên cảm nhận quy trình minh bạch, biết rõ mình đang ở bước nào và cần làm gì tiếp theo.

## 8. Open Items riêng cho vai trò Sinh viên

-   Danh sách chi tiết **những trường nào** sinh viên còn được phép sửa sau khi đã nộp hồ sơ.
-   Quy tắc giới hạn **số lần đổi đợt / đổi ngành** cho mỗi hồ sơ.
-   Quy ước cụ thể về:
    -   Thời gian giữ hiệu lực của mã tra cứu / phiên đăng nhập nếu dùng cơ chế nhẹ thay cho full auth portal.
    -   Chính sách dung lượng, định dạng file và cơ chế xoá file cũ khi upload lại.
