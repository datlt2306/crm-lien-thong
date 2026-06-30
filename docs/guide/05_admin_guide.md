# Bài 5: Hướng dẫn dành cho Quản trị viên

Tài liệu này hướng dẫn quản trị viên cấu hình đợt tuyển sinh, chỉ tiêu, chính sách hoa hồng và một số thiết lập vận hành.

## 1. Thiết lập đợt tuyển sinh

Quản trị viên có thể tạo và cập nhật các đợt tuyển sinh trong mục **Đợt tuyển sinh**.

Thông tin thường cần khai báo:

- Tên đợt
- Thời gian bắt đầu
- Thời gian kết thúc
- Trạng thái hoạt động

## 2. Thiết lập chỉ tiêu

Hệ thống hiện quản lý chỉ tiêu trong mục **Đợt tuyển & Chỉ tiêu** và **Chỉ tiêu năm**.

Khi cấu hình, cần kiểm tra:

- Ngành học
- Hệ đào tạo
- Năm hoặc đợt tuyển sinh liên quan
- Số lượng mục tiêu hoặc giới hạn tiếp nhận

Lưu ý:

- Chỉ tiêu không bị trừ ngay khi học viên vừa đăng ký.
- Chỉ tiêu được tiêu thụ khi thanh toán được xác minh theo logic hệ thống.

## 3. Cài đặt chính sách hoa hồng

Chính sách hoa hồng được quản lý trong mục **Chính sách hoa hồng**.

Mỗi cấu hình thường gồm:

- Đối tượng áp dụng
- Hệ đào tạo hoặc điều kiện áp dụng
- Mức tiền chi trả
- Thời điểm chi trả

Hai mốc chi trả hiện có:

- **Mùng 5 tháng sau (Sau khi nộp phí)**
- **Sau khi sinh viên nhập học thực tế**

## 4. Chuyển chương trình hoặc xử lý thay đổi hồ sơ

Khi học viên đổi chương trình hoặc phát sinh thay đổi lớn:

1. Kiểm tra tác động đến chỉ tiêu.
2. Kiểm tra tác động đến thanh toán.
3. Kiểm tra tác động đến hoa hồng.
4. Thực hiện thay đổi theo đúng màn hình quản trị đang được cấp quyền.

## 5. Khôi phục hoặc cập nhật hồ sơ cũ

Với các hồ sơ quay lại xử lý:

- Cần xác định rõ trạng thái cần phục hồi
- Kiểm tra lại dữ liệu tuyển sinh, thanh toán và chỉ tiêu trước khi lưu

## 6. Cấu hình thời gian giữ lead

Hệ thống dùng biến môi trường:

```env
LEAD_LOCK_DAYS=14
```

Giá trị mặc định hiện tại là **14 ngày**.

Sau khi thay đổi cấu hình, cần nạp lại cấu hình hệ thống để áp dụng.

## 7. Lưu ý quản trị

- Chỉ thay đổi cấu hình khi đã hiểu rõ tác động nghiệp vụ.
- Khi đổi chính sách hoa hồng hoặc quota, nên thông báo cho kế toán và tuyển sinh.
- Nên cập nhật lại tài liệu hướng dẫn nếu có thay đổi màn hình hoặc thuật ngữ.
