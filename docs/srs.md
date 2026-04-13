---
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

- Sinh viên chỉ điền rất ít thông tin ban đầu, thiếu dữ liệu để đánh giá điều kiện học tập.
- Gần đến kỳ thi, cô Ly phải gọi điện từng sinh viên để nhắc nộp hồ sơ, kiểm tra thiếu sót và nhập tay rất nhiều thông tin.
- Việc kiểm tra điều kiện ngành (ngành tốt nghiệp có phù hợp với ngành đăng ký hay không) đang làm thủ công, dễ sai sót.
- Sinh viên thường xuyên xin dời đợt thi hoặc đổi nguyện vọng, gây khó khăn trong việc theo dõi lịch sử và cập nhật dữ liệu.
- Dữ liệu hồ sơ, thanh toán, hoa hồng và danh sách gửi Trường bị phân tán ở nhiều file Excel và Google Drive.

**Ai đang bị ảnh hưởng bởi vấn đề này?**

- Cô Ly: quá tải nhập liệu, kiểm tra hồ sơ và nhắc sinh viên.
- Cô Vinh: khó nắm tổng quan số lượng hồ sơ, chỉ tiêu theo ngành và theo đợt.
- Kế toán: đối soát và chi trả hoa hồng thủ công, tiềm ẩn rủi ro nhầm lẫn.
- Cộng tác viên: không theo dõi được tiến độ hồ sơ sinh viên mình phụ trách.
- Sinh viên: không biết trạng thái hồ sơ, không rõ mình còn thiếu gì, phụ thuộc hoàn toàn vào việc được gọi nhắc.

**Tình trạng hiện tại / cách làm tạm thời**

- Form đăng ký chỉ thu thập thông tin tối thiểu.
- Cô Ly gọi điện, nhắn Zalo để bổ sung thông tin.
- Kiểm tra điều kiện ngành chủ yếu dựa vào kinh nghiệm cá nhân.
- Tổng hợp hồ sơ bằng Excel và upload thủ công lên Google Drive.

---

## Mục tiêu & Định hướng

**Chúng ta muốn đạt được điều gì?**

### Mục tiêu chính

- Xây dựng **SRS cho hệ thống CRM Tuyển sinh Liên thông theo mô hình API-first**.
- Chuẩn hoá toàn bộ quy trình tuyển sinh liên thông thành các **nghiệp vụ backend rõ ràng, có thể kiểm soát bằng API**.
- Giảm tối thiểu 50–70% khối lượng nhập tay của cô Ly thông qua phân quyền nhập liệu và checklist số hoá.
- Cho phép sinh viên tự theo dõi, tự nhập và tự bổ sung hồ sơ thông qua các API portal.
- Kiểm soát chặt chẽ điều kiện ngành, đợt tuyển sinh và chỉ tiêu ngay từ tầng backend.

### Mục tiêu phụ

- Tạo nền tảng backend ổn định để dễ dàng phát triển các client sau này (Admin Web, Portal Sinh viên, Mobile App).
- Minh bạch hoá trạng thái hồ sơ và hoa hồng cộng tác viên thông qua API.
- Dễ dàng xuất dữ liệu đúng định dạng để gửi Trường thông qua API export.
- Lưu trữ tập trung hồ sơ và file Excel trên Google Drive, quản lý bằng link và metadata.

### Ngoài phạm vi (không làm trong giai đoạn này)

- Không xây dựng giao diện người dùng hoàn chỉnh (Admin UI / Portal sinh viên chỉ ở mức tối thiểu để test API nếu cần).
- Không xây dựng hệ thống học tập (LMS).
- Không xử lý nghiệp vụ đào tạo sau khi sinh viên đã nhập học.
- Không thay thế hoàn toàn Google Drive, chỉ tích hợp và liên kết.

---

## Câu chuyện người dùng & Trường hợp sử dụng

**Người dùng sẽ tương tác với hệ thống như thế nào?**

### Sinh viên

- Là sinh viên, tôi muốn đăng nhập để xem trạng thái hồ sơ để biết mình còn thiếu gì.
- Là sinh viên, tôi muốn tự nhập và cập nhật thông tin cá nhân để không phải gửi nhiều lần qua Zalo.
- Là sinh viên, tôi muốn tải lên giấy tờ theo checklist để hồ sơ được duyệt nhanh hơn.

### Cộng tác viên (CTV)

- Là CTV, tôi muốn tạo lead sinh viên để theo dõi tiến độ tuyển sinh.
- Là CTV, tôi muốn xem trạng thái hồ sơ sinh viên của mình để biết khi nào cần nhắc.

### Cô Ly (phụ trách hồ sơ)

- Là người phụ trách hồ sơ, tôi muốn xem checklist để biết sinh viên còn thiếu giấy tờ nào.
- Là người phụ trách hồ sơ, tôi muốn hệ thống tự kiểm tra điều kiện ngành để giảm rủi ro sai sót.
- Là người phụ trách hồ sơ, tôi chỉ muốn nhập hoặc sửa thông tin khi thật sự cần để giảm nhập tay.

### Cô Vinh (quản trị)

- Là quản trị, tôi muốn tạo ngành, đợt và chỉ tiêu để quản lý quy mô tuyển sinh.
- Là quản trị, tôi muốn xem dashboard tổng hợp để nắm toàn bộ tình hình.

### Kế toán

- Là kế toán, tôi muốn đối soát phí và hoa hồng để chi trả chính xác và minh bạch.

**Các trường hợp đặc biệt (edge cases)**

- Sinh viên không đủ điều kiện ngành.
- Sinh viên xin dời sang đợt sau nhiều lần.
- Sinh viên nhập sai thông tin cần được chỉnh sửa và lưu lịch sử.
- Sinh viên đăng ký trực tiếp tại văn phòng tuyển sinh (**không qua CTV/Ref**) → hồ sơ phải có `source = walkin`, `collaborator_id = null`, **không phát sinh hoa hồng**.

---

## Tiêu chí thành công

**Khi nào thì coi như hệ thống đạt yêu cầu?**

- Ít nhất 70% hồ sơ được sinh viên tự nhập và tải đủ giấy tờ trước khi cô Ly kiểm tra.
- Thời gian xử lý trung bình một hồ sơ giảm tối thiểu 50%.
- Không xảy ra trường hợp gửi hồ sơ sai ngành cho Trường.
- Kế toán có thể đối soát và chi trả hoa hồng hoàn toàn trong hệ thống.
- Xuất được file Excel đúng định dạng Trường chỉ với một thao tác.

---

## Ràng buộc & Giả định

**Những giới hạn cần tuân thủ trong SRS**

### Ràng buộc kỹ thuật

- Hệ thống được thiết kế và triển khai theo mô hình **Backend-only / API-first**.
- Toàn bộ nghiệp vụ phải được thể hiện rõ ở tầng API (không phụ thuộc UI).
- API có thể là REST hoặc GraphQL, nhưng phải thống nhất và có tài liệu.
- Bắt buộc có:
    - Phân quyền theo vai trò
    - Workflow trạng thái hồ sơ
    - Kiểm soát điều kiện ngành
    - Log thay đổi dữ liệu (audit log)
    - Upload tài liệu và quản lý file
    - Tích hợp Google Drive
    - Xuất Excel theo template tuyển sinh

### Ràng buộc bảo mật & Quy tắc vận hành lõi

- **Ví nội bộ & Hoa hồng (Wallet)**: Quản lý hoa hồng và ví điện tử nội bộ phải tuân thủ nghiêm ngặt nguyên tắc **Chặn Giao Dịch Âm (Negative Amount Exploit)** và có Lock File (`DB::transaction`) nhằm tránh Data Race (Race Condition).
- **Chỉ tiêu tuyển sinh (Quota)**: Quỹ chỉ tiêu được lưu giữ an toàn, khi một thanh toán được duyệt Kế toán mới trừ chỉ tiêu. Nếu hồ sơ bị hủy/trả về, slot chỉ tiêu bắt buộc phải được **Hoàn lại (Auto-Refund)** về kho của Tổ chức đó.
- **Mô hình Cộng tác viên**: Toàn bộ quy trình Tuyển dụng/Ghi nhận Cộng tác viên chạy theo mô hình **Hoa hồng Trực tiếp 1 cấp** (Direct Commission). Không cho phép đăng ký CTV tự do (Public form) mà phải được cấp quyền tài khoản qua Quản trị/Chủ đơn vị.

### Ràng buộc nghiệp vụ

- Quy trình thủ công hiện tại vẫn phải chạy song song trong giai đoạn đầu.
- Không được làm gián đoạn hoạt động tuyển sinh đang diễn ra.
- Cô Ly vẫn là người kiểm soát cuối cùng trước khi gửi hồ sơ cho Trường.

### Ràng buộc thời gian / ngân sách

- Ưu tiên triển khai theo từng module backend (MVP → mở rộng).
- Ngân sách và nguồn lực IT có hạn, cần thiết kế đủ dùng nhưng không thừa.

### Giả định

- Sinh viên có smartphone và có thể tương tác với API thông qua portal/web/app.
- Cô Ly, CTV, Kế toán sẽ sử dụng hệ thống thông qua các client được xây dựng sau.

---

**Những giới hạn cần tuân thủ**

### Ràng buộc kỹ thuật

- Giai đoạn hiện tại hệ thống được triển khai theo mô hình **API-first / Backend-only**.
- Hệ thống chỉ cần xây dựng các API nghiệp vụ (REST hoặc GraphQL), chưa yêu cầu xây dựng giao diện người dùng hoàn chỉnh.
- API phải được thiết kế đủ tổng quát để có thể tích hợp với nhiều client trong tương lai (Admin web, Portal sinh viên, Mobile app).
- Dù chỉ triển khai API, hệ thống vẫn bắt buộc có đầy đủ: phân quyền theo vai trò, workflow trạng thái hồ sơ, kiểm soát nghiệp vụ, log thay đổi dữ liệu.
- Cần hỗ trợ upload file, liên kết Google Drive và xuất Excel theo template tuyển sinh.

### Ràng buộc nghiệp vụ

- Quy trình thủ công hiện tại vẫn phải chạy song song trong giai đoạn đầu.
- Không được làm gián đoạn hoạt động tuyển sinh đang diễn ra.

### Ràng buộc thời gian / ngân sách

- Ưu tiên triển khai từng phần (MVP → mở rộng).
- Ngân sách và nguồn lực IT có hạn.

### Giả định

- Sinh viên có smartphone và có thể tự tải hồ sơ.
- Cô Ly và CTV sẵn sàng chuyển dần sang CRM.

---

## Bổ sung: Yêu cầu dữ liệu hồ sơ sinh viên (Data Requirements)

Hệ thống CRM phải hỗ trợ đầy đủ các trường dữ liệu hồ sơ sinh viên theo đúng biểu mẫu tuyển sinh hiện hành, đồng thời tối ưu hoá việc phân quyền nhập liệu nhằm giảm tải nhập tay cho cô Ly.

### 1. Nguyên tắc thiết kế dữ liệu

- Trường dữ liệu nào sinh viên có thể tự nhập và tự cung cấp minh chứng thì cho phép sinh viên nhập trực tiếp.
- Cô Ly chỉ kiểm tra, duyệt, chỉnh sửa các trường hợp sai hoặc thiếu (ngoại lệ).
- Các trường có thể suy ra, tính toán hoặc tra cứu thì hệ thống tự động sinh.
- Mọi chỉnh sửa đều phải được lưu lịch sử (ai sửa, thời điểm, nội dung thay đổi).

---

### 2. Nhóm trường hệ thống tự sinh

- STT
- Ngày tháng (ngày tạo / ngày cập nhật hồ sơ)
- Trạng thái hồ sơ
- Tình trạng hồ sơ
- Phiếu tuyển sinh
- Lệ phí (tự động theo Hệ ĐKLT)
- Hình thức tuyển sinh
- KV ưu tiên (tính theo địa chỉ hoặc trường THPT nếu có quy tắc)

---

### 3. Nhóm trường sinh viên tự nhập

#### 3.1 Thông tin cá nhân

- Họ và tên
- Ngày sinh
- Nơi sinh
- Hộ khẩu thường trú
- Số điện thoại
- Dân tộc
- Giới tính
- CCCD
- Ngày cấp CCCD
- Nơi cấp CCCD

#### 3.2 Thông tin THPT

- Tên trường THPT
- Mã trường
- Tên tỉnh/TP
- Mã tỉnh
- Tên quận/huyện
- Mã quận/huyện
- Năm tốt nghiệp THPT
- Học lực cả năm
- Hạnh kiểm

#### 3.3 Thông tin văn bằng Cao đẳng / Trung cấp

- Trường tốt nghiệp CĐ
- Ngành tốt nghiệp CĐ
- Xếp loại
- Hệ đào tạo tốt nghiệp
- Năm tốt nghiệp
- Số hiệu bằng TN CĐ
- Số vào sổ cấp bằng TN CĐ
- Ngày ký bằng TN CĐ
- Người ký bằng TN CĐ
- Thông tin Trung cấp (nếu có)

#### 3.4 Tải lên giấy tờ minh chứng

- Bằng TN CĐ (bản sao / bản scan)
- Bảng điểm CĐ
- Bằng TN THPT
- Giấy khai sinh
- CCCD (mặt trước / sau)
- Ảnh cá nhân
- Giấy khám sức khoẻ

---

### 4. Nhóm trường cô Ly hoàn thiện và duyệt

- Kiểm tra điều kiện ngành (Đạt / Không đạt / Cần xem xét)
- Ngành đăng ký liên thông
- Trường đăng ký liên thông
- Hệ đăng ký liên thông
- Đợt đăng ký liên thông
- Ghi chú nghiệp vụ

---

### 5. Quy tắc khoá – duyệt – chỉnh sửa dữ liệu

- Sinh viên được chỉnh sửa dữ liệu trước khi nộp hồ sơ.
- Sau khi nộp, các trường quan trọng sẽ bị khoá và chỉ được sửa khi gửi yêu cầu.
- Cô Ly có quyền chỉnh sửa khi duyệt hồ sơ, bắt buộc nhập lý do chỉnh sửa.
- Hệ thống lưu song song giá trị sinh viên nhập và giá trị đã duyệt.
- Quy tắc nguồn tuyển sinh:
    - Nếu `source = ref` (CTV/Đối tác giới thiệu) thì có thể gán `collaborator_id` để ghi nhận kênh giới thiệu và tính hoa hồng theo chính sách.
    - Nếu `source = walkin` (đến trực tiếp / văn phòng tuyển sinh) thì **bắt buộc** `collaborator_id = null` và **không ghi nhận hoa hồng**.

---

### 6. Yêu cầu tích hợp Google Drive & Excel

- Mỗi đợt tuyển sinh có một thư mục Google Drive riêng.
- Mỗi sinh viên có một thư mục con chứa toàn bộ giấy tờ.
- CRM phải xuất được file Excel đúng template tuyển sinh hiện hành.
- Link Google Drive và file Excel được lưu trực tiếp trong hồ sơ sinh viên.

---

## Câu hỏi & Vấn đề cần làm rõ (Open Items cho SRS)

- Danh sách mapping ngành đầu vào – đầu ra chính thức từ Trường để đưa vào rule backend.

- Template Excel cuối cùng Trường yêu cầu (có thay đổi theo từng đợt hay không).

- Những trường dữ liệu nào sinh viên được phép chỉnh sửa sau khi đã nộp hồ sơ (rule khoá field).

- Cách xử lý các trường hợp ngoại lệ: ngành gần, bằng đặc thù, chuyển đợt nhiều lần.

- Thứ tự triển khai các module API:
    - Hồ sơ sinh viên
    - Quản lý ngành / đợt / chỉ tiêu
    - Thanh toán & hoa hồng
    - Export & Google Drive integration

- Danh sách mapping ngành đầu vào – đầu ra chính thức từ Trường.

- Template Excel cuối cùng Trường yêu cầu (có thay đổi theo từng đợt hay không).

- Các trường dữ liệu nào sinh viên được phép chỉnh sửa sau khi đã nộp hồ sơ.

- Quy định xử lý các trường hợp ngoại lệ (bằng liên thông đặc thù, ngành gần).

- Lộ trình triển khai: ưu tiên module hồ sơ sinh viên, quản lý ngành hay kế toán trước.
