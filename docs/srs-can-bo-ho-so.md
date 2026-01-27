---
phase: requirements
role: can_bo_ho_so
title: SRS – Vai trò Cán bộ hồ sơ (Cô Ly)
description: Đặc tả yêu cầu nghiệp vụ và hành vi hệ thống cho vai trò Cán bộ hồ sơ trong CRM Tuyển sinh Liên thông
---

## 1. Giới thiệu & Bối cảnh

### 1.1. Mô tả vai trò

-   **Cán bộ hồ sơ (Cô Ly)** là người:
    -   Chịu trách nhiệm chính trong việc **kiểm tra, hoàn thiện và duyệt hồ sơ sinh viên**.
    -   Là đầu mối phối hợp giữa sinh viên, CTV, Kế toán và phía Trường.
    -   Kiểm tra điều kiện ngành, tình trạng giấy tờ, đảm bảo hồ sơ đủ chuẩn trước khi gửi Trường.

### 1.2. Phạm vi SRS cho Cán bộ hồ sơ

-   Mô tả các nghiệp vụ backend cho phép Cán bộ hồ sơ:
    -   Xem, lọc, tìm kiếm danh sách hồ sơ sinh viên.
    -   Kiểm tra checklist giấy tờ, đánh dấu thiếu/đủ, yêu cầu bổ sung.
    -   Kiểm tra và đánh giá **điều kiện ngành** (Đạt/Không đạt/Cần xem xét).
    -   Chỉnh sửa thông tin hồ sơ khi cần, kèm bắt buộc lý do, và lưu lịch sử thay đổi.
    -   Phối hợp với Kế toán trong bước xác nhận thanh toán liên quan tới tuyển sinh.
    -   Hỗ trợ xuất dữ liệu đúng format để gửi Trường (ở mức thao tác nghiệp vụ, không chi tiết kỹ thuật xuất file).

## 2. Mục tiêu theo vai trò

-   Giảm tối đa việc nhập tay lặp lại, tập trung vào:
    -   Kiểm tra chất lượng hồ sơ.
    -   Ra quyết định duyệt/không duyệt.
-   Đảm bảo:
    -   Không gửi hồ sơ sai ngành hoặc thiếu giấy tờ cho Trường.
    -   Có đầy đủ log và bút tích điện tử cho mọi chỉnh sửa quan trọng.

## 3. Use Case chính cho Cán bộ hồ sơ

### 3.1. Xem danh sách và lọc hồ sơ sinh viên

-   **Mô tả**:
    -   Cán bộ hồ sơ cần xem danh sách các hồ sơ thuộc phạm vi xử lý của mình, tập trung vào các hồ sơ:
        -   Đã nộp.
        -   Đã được CTV xác nhận nộp tiền (submitted/verified).
        -   Thiếu giấy tờ hoặc cần kiểm tra điều kiện ngành.
-   **Căn cứ từ `StudentResource`**:
    -   Đối với user có role `document` hoặc `accountant`, query mặc định:
        -   Lọc `Student` thông qua quan hệ `payment` với `status` thuộc `['submitted', 'verified']`.
-   **Yêu cầu hệ thống**:
    -   API danh sách hồ sơ cho role `document`:
        -   Lọc theo trạng thái hồ sơ, đợt tuyển sinh, ngành, trạng thái giấy tờ, trạng thái thanh toán.
        -   Hỗ trợ phân trang, tìm kiếm nhanh theo tên, số điện thoại, mã hồ sơ.

### 3.2. Xem chi tiết hồ sơ & checklist giấy tờ

-   **Mô tả**:
    -   Cán bộ hồ sơ cần xem chi tiết:
        -   Thông tin cá nhân, học tập, văn bằng, thông tin CTV, thông tin tổ chức.
        -   Danh sách giấy tờ đã upload và tình trạng duyệt từng loại.
-   **Yêu cầu hệ thống**:
    -   API chi tiết hồ sơ:
        -   Kết hợp dữ liệu từ:
            -   `Student`.
            -   `StudentDocument` (hoặc tương đương).
            -   `Payment` (ở mức xem được).
            -   Thông tin CTV, Organization nếu cần.
    -   Checklist giấy tờ:
        -   Thể hiện đầy đủ các loại giấy tờ yêu cầu (theo mục 3.4 trong SRS tổng).
        -   Trạng thái từng giấy tờ: chưa nộp / đã nộp / không yêu cầu / bị từ chối (kèm lý do).

### 3.3. Đánh giá điều kiện ngành & quyết định hồ sơ

-   **Mô tả**:
    -   Cán bộ hồ sơ kiểm tra **điều kiện ngành**: ngành tốt nghiệp đầu vào có phù hợp với ngành đăng ký liên thông hay không.
-   **Yêu cầu hệ thống**:
    -   API cho phép:
        -   Cập nhật trường:
            -   `Kiểm tra điều kiện ngành`: Đạt / Không đạt / Cần xem xét.
            -   `Ngành đăng ký liên thông`, `Trường đăng ký`, `Đợt đăng ký`, `Hệ đăng ký`.
        -   Ghi lại:
            -   Người thực hiện (Cán bộ hồ sơ).
            -   Thời điểm.
            -   Ghi chú nghiệp vụ bắt buộc khi chọn Không đạt hoặc Cần xem xét.
    -   Hệ thống có thể:
        -   Áp dụng rule mapping ngành đầu vào – đầu ra để gợi ý hoặc cảnh báo (vấn đề này vẫn là open item cần cập nhật thêm).

### 3.4. Cập nhật / chỉnh sửa thông tin hồ sơ (ngoại lệ)

-   **Mô tả**:
    -   Khi sinh viên nhập sai hoặc cần điều chỉnh theo giấy tờ minh chứng, Cán bộ hồ sơ có quyền chỉnh sửa một số trường.
-   **Yêu cầu hệ thống**:
    -   API cập nhật hồ sơ với rule:
        -   Chỉ cho phép Cán bộ hồ sơ sửa các trường được phép (không vi phạm rule khoá dữ liệu).
        -   **Bắt buộc** nhập lý do chỉnh sửa.
    -   Hệ thống lưu:
        -   Lịch sử chỉnh sửa chi tiết (old value, new value, user, thời gian, lý do).
        -   Song song giá trị:
            -   **Sinh viên nhập ban đầu**.
            -   **Giá trị đã duyệt cuối cùng**.

### 3.5. Yêu cầu bổ sung giấy tờ & giao tiếp với sinh viên/CTV

-   **Mô tả**:
    -   Khi hồ sơ thiếu giấy tờ hoặc giấy tờ không hợp lệ, Cán bộ hồ sơ cần yêu cầu sinh viên/CTV bổ sung.
-   **Yêu cầu hệ thống**:
    -   API cho phép Cán bộ hồ sơ:
        -   Đánh dấu trạng thái từng giấy tờ: Thiếu / Không hợp lệ / Đã đủ.
        -   Gửi yêu cầu bổ sung (lưu thành log hoặc notification).
        -   Ghi ghi chú cụ thể cho từng loại giấy tờ.
    -   Cơ chế thông báo:
        -   Gửi tới sinh viên và/hoặc CTV qua kênh notification đã cấu hình (email, push, v.v.).

### 3.6. Phối hợp xác nhận thanh toán với Kế toán

-   **Mô tả**:
    -   Trong một số trường hợp, Cán bộ hồ sơ cũng tham gia xác nhận số tiền sinh viên đã nộp đăng ký (bên cạnh Kế toán).
-   **Căn cứ từ `PaymentPolicy`**:
    -   Hàm `verify`:
        -   Cho phép user có quyền `verify_payment` hoặc có role `accountant`, `document` xác nhận số tiền sinh viên nộp.
-   **Yêu cầu hệ thống**:
    -   API cho phép Cán bộ hồ sơ:
        -   Thực hiện thao tác “Xác nhận” đối với một `Payment` đang ở trạng thái phù hợp (ví dụ: đã có chứng từ/đã thu).
        -   Ghi chú khi xác nhận hoặc từ chối.
    -   Upload phiếu thu:
        -   Thuộc quyền riêng của role `accountant` (Kế toán), Cán bộ hồ sơ chỉ xem được.

### 3.7. Chuẩn bị dữ liệu gửi Trường (Export)

-   **Mô tả**:
    -   Cán bộ hồ sơ cần xuất danh sách hồ sơ đã hoàn thiện để gửi Trường (thường dưới dạng Excel theo template).
-   **Yêu cầu hệ thống**:
    -   Tầng backend:
        -   Hỗ trợ tập hợp dữ liệu đúng format (theo template do Trường cung cấp).
    -   Với vai trò Cán bộ hồ sơ:
        -   Có quyền:
            -   Chọn đợt tuyển sinh / ngành / tập hồ sơ cần export.
            -   Thực hiện thao tác “Xuất danh sách gửi Trường”.
        -   Không tự ý thay đổi format file, chỉ thao tác trên dữ liệu và trigger export.

## 4. Quy tắc quyền hạn & bảo mật cho Cán bộ hồ sơ

-   Cán bộ hồ sơ **được quyền**:
    -   Xem các hồ sơ sinh viên ở trạng thái cần xử lý (theo lọc của hệ thống).
    -   Xem chi tiết hồ sơ, giấy tờ, thanh toán liên quan.
    -   Cập nhật trạng thái hồ sơ, điều kiện ngành, checklist giấy tờ.
    -   Xác nhận thanh toán (ở mức verify), nhưng **không** upload phiếu thu (trừ khi có thêm quyền).
-   Cán bộ hồ sơ **không được**:
    -   Thay đổi chính sách hoa hồng, quota, cấu hình hệ thống.
    -   Truy cập danh sách hồ sơ ngoài phạm vi được phân công (nếu về sau có phân tách theo tổ chức).
-   Mọi thao tác cập nhật:
    -   Phải được log đầy đủ (audit log) phục vụ truy xuất trách nhiệm.

## 5. Dữ liệu & tích hợp liên quan đến Cán bộ hồ sơ

-   **Dữ liệu chính**:
    -   `Student`: toàn bộ thông tin hồ sơ.
    -   `StudentDocument`: giấy tờ minh chứng và trạng thái duyệt từng giấy tờ.
    -   `Payment`: trạng thái thanh toán, số tiền, ghi chú thanh toán.
    -   Các thực thể liên quan:
        -   `Program`, `Major`, `Intake`, `Quota`, `Organization`, `Collaborator`.
-   **Tích hợp**:
    -   Google Drive:
        -   Mỗi hồ sơ có thư mục riêng trong thư mục đợt trên Drive.
        -   Cán bộ hồ sơ truy cập link Drive từ metadata trong hồ sơ để kiểm tra file gốc nếu cần.
    -   Excel:
        -   Xuất danh sách hồ sơ theo template của Trường.
    -   Notification:
        -   Thông báo đến sinh viên/CTV khi có yêu cầu bổ sung, thay đổi trạng thái quan trọng.

## 6. Tiêu chí thành công theo vai trò Cán bộ hồ sơ

-   Thời gian xử lý trung bình một hồ sơ giảm tối thiểu 50% so với quy trình thủ công.
-   Không phát sinh trường hợp hồ sơ gửi Trường:
    -   Sai ngành.
    -   Thiếu giấy tờ bắt buộc.
-   Tỷ lệ hồ sơ phải trả về vì lỗi nhập liệu chủ quan giảm đáng kể, nhờ:
    -   Checklist rõ ràng.
    -   Rule điều kiện ngành được backend hỗ trợ.

## 7. Open Items riêng cho vai trò Cán bộ hồ sơ

-   Danh sách mapping ngành đầu vào – đầu ra chính thức để:
    -   Tự động gợi ý Đạt/Không đạt/Cần xem xét.
-   Quy tắc chi tiết về:
    -   Trường nào cán bộ hồ sơ được phép chỉnh sửa sau khi hồ sơ đã nộp.
    -   Phạm vi hồ sơ mà mỗi cán bộ hồ sơ được quyền xem (theo tổ chức, theo đợt, theo ngành, v.v.).
-   Chi tiết luồng phối hợp với Kế toán:
    -   Cán bộ hồ sơ xác nhận ở mức độ nào.
    -   Kế toán xác nhận và phát hành phiếu thu ở mức độ nào.
