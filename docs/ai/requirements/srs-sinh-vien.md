---
phase: requirements
role: sinh_vien
title: SRS – Vai trò Sinh viên
description: Đặc tả yêu cầu nghiệp vụ và hành vi hệ thống cho vai trò Sinh viên trong CRM Tuyển sinh Liên thông
---

## 1. Giới thiệu & Bối cảnh

### 1.1. Mô tả vai trò

-   **Sinh viên** là người đăng ký tham gia chương trình tuyển sinh liên thông thông qua các kênh (form đăng ký website, link giới thiệu CTV, v.v.).
-   Sinh viên cung cấp thông tin cá nhân ban đầu; việc số hóa và tải lên giấy tờ minh chứng (bằng cấp, CCCD) sẽ do **Cộng tác viên** hoặc **Cán bộ văn phòng** thực hiện dựa trên hồ sơ gốc của sinh viên.
-   Sinh viên không trực tiếp thao tác trên hệ thống quản trị, nhưng có thể theo dõi tiến độ thông qua trang tra cứu công khai.

### 1.2. Phạm vi SRS cho vai trò Sinh viên

-   Mô tả các **API / nghiệp vụ backend liên quan trực tiếp** đến việc sinh viên:
    -   Khởi tạo hồ sơ qua form đăng ký.
    -   Theo dõi trạng thái hồ sơ và thanh toán qua mã tra cứu (Profile Code).
    -   Tương tác gián tiếp với CTV, Cán bộ hồ sơ, Kế toán qua thông báo và trạng thái hiển thị.
-   Không mô tả chi tiết UI, chỉ tập trung vào **dữ liệu, luồng nghiệp vụ và quyền truy cập**.

## 2. Mục tiêu theo vai trò

-   Sinh viên có thể **tự nhập** dữ liệu hồ sơ thông qua form đăng ký công khai, giúp quy trình diễn ra nhanh chóng.
-   Sau khi đăng ký, sinh viên sử dụng **Mã hồ sơ (Profile Code)** để tra cứu và theo dõi trạng thái hồ sơ trực tuyến.
-   Quy trình nộp lệ phí và bổ sung giấy tờ minh chứng được thực hiện thông qua Cộng tác viên hoặc Cán bộ văn phòng; sinh viên chỉ quan sát kết quả xác nhận trên hệ thống.
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
        -   Thông tin liên hệ cơ bản (họ tên, số điện thoại, email bắt buộc).
        -   Thông tin nhận diện (CCCD hoặc trường dữ liệu tương đương).
    -   Gắn kết hồ sơ với:
        -   **CTV** (nếu sinh viên đi qua link giới thiệu hoặc được CTV nhập giúp).
        -   **Đợt tuyển sinh / ngành đăng ký dự kiến** (nếu đã có).
    -   Sinh viên sau đó có thể sử dụng mã tra cứu hồ sơ (profile code) ngẫu nhiên để tiếp tục hoàn thiện hồ sơ và theo dõi tiến độ.

### 3.2. Tra cứu trạng thái hồ sơ
-   **Mô tả**: Sinh viên sử dụng Mã hồ sơ được cấp sau khi đăng ký để kiểm tra tiến độ xử lý.
-   **Tính năng chính**:
    -   Trang tra cứu công khai (không yêu cầu đăng nhập tài khoản).
    -   Hiển thị thông tin tổng quan: Họ tên, Ngành học, Hệ đào tạo, Đợt tuyển sinh.
    -   Hiển thị **Trạng thái hồ sơ** (ví dụ: Mới, Đã liên hệ, Đã nộp, Đã duyệt, v.v.).
    -   Hiển thị **Trạng thái thanh toán** lệ phí (Chưa nộp, Chờ xác minh, Đã nộp).
-   **Quy tắc**:
    -   Sinh viên không thể chỉnh sửa thông tin sau khi đã nhấn "Gửi đăng ký".
    -   Mọi yêu cầu thay đổi thông tin sau khi nộp phải liên hệ trực tiếp với CTV hỗ trợ hoặc hotline tuyển sinh.

### 3.3. Quy trình nộp giấy tờ minh chứng (Nghiệp vụ CTV/Cán bộ)
-   **Mô tả**: Việc thu thập và tải lên bản scan/ảnh các giấy tờ minh chứng (CCCD, Bằng tốt nghiệp, Học bạ...) hiện do **Cộng tác viên** hoặc **Cán bộ hồ sơ** thực hiện trên giao diện quản trị.
-   **Lưu ý**: Sinh viên không trực tiếp tải file lên hệ thống ở giai đoạn này để đảm bảo tính xác thực và đúng định dạng yêu cầu.
-   **Quy tắc lưu trữ**:
    -   Mọi file upload phải được lưu trữ an toàn (Google Drive Private).
    -   Tên file được chuẩn hóa theo định dạng: `{Mã_HS}_{Tên}_{Ngành}_{Hệ}.ext`.
    -   Tích hợp Google Drive:
        -   Mỗi sinh viên sẽ có một thư mục riêng trong thư mục đợt tuyển sinh:
            -   `/DriveFolder/{Intake}/{Student}/...`
        -   Link Google Drive được lưu trong hồ sơ sinh viên để cán bộ hồ sơ và quản lý truy cập. Quyền truy cập file trên Drive được thiết lập là **Private** (chỉ hệ thống và người có thẩm quyền truy cập).
        -   Hệ thống sử dụng **Token-based Authentication** (mã token bảo mật đi kèm URL) để cho phép sinh viên xem bill/phiếu thu của chính mình mà không cần đăng nhập portal phức tạp, đồng thời ngăn chặn tấn công IDOR.
    -   Kiểm soát kích thước file, loại file (PDF/JPEG/PNG, v.v.).

### 3.4. Nộp hồ sơ tuyển sinh
-   **Mô tả**: Sinh viên thực hiện đăng ký thông qua form website.
-   **Nghiệp vụ**:
    -   Khi sinh viên nhấn "Gửi đăng ký", bản ghi `Student` được tạo ở trạng thái `new`.
    -   Mã hồ sơ (Profile Code) được sinh tự động và hiển thị cho sinh viên.
    -   Hệ thống gửi email thông báo đăng ký thành công cho sinh viên.

### 3.5. Theo dõi trạng thái hồ sơ

-   **Mô tả**:
    -   Sinh viên xem trạng thái xử lý hồ sơ ở mọi thời điểm.
-   **Trạng thái hồ sơ (`status`)**:
    -   `new`: Mới đăng ký từ website/form.
    -   `contacted`: Đã liên hệ tư vấn.
    -   `submitted`: Đã nộp đầy đủ hồ sơ/lệ phí.
    -   `approved`: Đã duyệt trúng tuyển.
    -   `enrolled`: Đã nhập học chính thức.
    -   `rejected`: Hồ sơ bị loại.
-   **Trạng thái hồ sơ chi tiết (`application_status`)**:
    -   `draft`: Đang nhập (Admin/CTV tạo nháp).
    -   `pending_documents`: Thiếu giấy tờ.
    -   `submitted`: Đã nộp hồ sơ.
    -   `verified`: Đã xác minh.
    -   `eligible`: Đủ điều kiện.
    -   `ineligible`: Không đủ điều kiện.
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

### 3.7. Xin đổi đợt / đổi nguyện vọng
-   **Mô tả**: Sinh viên yêu cầu thay đổi thông tin ngành/đợt bằng cách liên hệ với CTV hỗ trợ.
-   **Nghiệp vụ**:
    -   Cộng tác viên hoặc Cán bộ hồ sơ thực hiện cập nhật trên hệ thống.
    -   Mọi thay đổi đều yêu cầu nhập **Lý do chỉnh sửa** và được lưu vào Nhật ký (Audit Log).

## 4. Quy tắc quyền hạn & bảo mật cho Sinh viên

-   Sinh viên **chỉ được truy cập**:
    -   Hồ sơ của chính mình.
    -   Các tài nguyên liên quan trực tiếp tới hồ sơ đó (document, payment, trạng thái).
-   Sinh viên **không được**:
    -   Xem danh sách tất cả sinh viên.
    -   Truy cập thông tin hoa hồng CTV, thông tin nội bộ của tổ chức.
-   Mọi API cho sinh viên cần:
    -   Xác thực (token bảo mật băm từ ID/UUID hoặc phiên đăng nhập).
    -   Sử dụng **UUID** thay cho ID tuần tự trong các URL công khai để ngăn chặn thu thập dữ liệu (data scraping).
    -   Kiểm tra quyền theo ID sinh viên gắn với tài khoản hoặc token hợp lệ.

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
    -   Thông tin cá nhân (email là trường bắt buộc).
    -   Mã hồ sơ (profile_code): được sinh tự động theo định dạng bảo mật (HS + Năm + 4 ký tự ngẫu nhiên + 3 số định danh).
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

-   Ít nhất **90% hồ sơ** được sinh viên tự nhập thông tin ban đầu thông qua form đăng ký.
-   Sinh viên có thể **tự tra cứu** trạng thái hồ sơ mà không phải gọi điện hỏi.
-   Số lần phải chỉnh sửa dữ liệu do nhập sai sau khi nộp hồ sơ giảm rõ rệt (nhờ rule khoá field và checklist rõ ràng).
-   Sinh viên cảm nhận quy trình minh bạch, biết rõ mình đang ở bước nào và cần làm gì tiếp theo.

## 8. Trạng thái các vấn đề mở (Open Items)
-   **Đã giải quyết**:
    -   Email là bắt buộc và duy nhất cho mỗi sinh viên.
    -   Mã hồ sơ (Profile Code) được sinh tự động theo định dạng bảo mật.
    -   File tài liệu được lưu trữ riêng tư trên Google Drive, truy cập qua URL bảo mật kèm Token.
    -   Sử dụng UUID cho toàn bộ giao diện công khai để ngăn chặn IDOR.
-   **Cần làm rõ thêm**:
    -   Danh sách chi tiết các trường thông tin được phép sửa đổi sau khi hồ sơ đã "Nộp".
    -   Quy tắc giới hạn số lần thay đổi Ngành/Đợt đăng ký.
