# Hướng dẫn sử dụng hệ thống hoa hồng mới

## Tổng quan

Hệ thống hoa hồng đã được cập nhật theo nghiệp vụ chuẩn với các tính năng mới:

### Luồng nghiệp vụ chuẩn:

1. **Sinh viên chuyển tiền cho Org (Cô Vinh)**
2. **Org chỉ trả tiền cho CTV cấp 1:**
    - Chính quy = 1.750k
    - VHVLV = 750k
3. **CTV cấp 1 cấu hình mức chia cho CTV cấp 2:**
    - Chọn số tiền cho hệ CQ và VHVLV
    - Chọn hình thức chi: trả ngay hoặc trả khi nhập học
4. **CTV cấp 2 chỉ được xem mức mình được nhận**
5. **Hoa hồng tuyến dưới được trích từ phần của CTV cấp 1**

## Các tính năng mới

### 1. Quan hệ cha-con cho CTV

-   Mỗi CTV có `parent_id` (upline_id)
-   Hiển thị dạng cây trong danh sách
-   CTV cấp 1: không có upline
-   CTV cấp 2: có upline là CTV cấp 1

### 2. Cấu hình hoa hồng tuyến dưới

**Vị trí:** Admin > Quản lý hoa hồng > Cấu hình hoa hồng tuyến dưới

**Chức năng:**

-   Liệt kê danh sách CTV con của CTV cấp 1
-   Nhập số tiền theo từng hệ (CQ, VHVLV)
-   Chọn hình thức thanh toán:
    -   **Trả ngay:** Commission được tạo với trạng thái `payable`
    -   **Trả khi nhập học:** Commission được tạo với trạng thái `pending`
-   Lưu cấu hình riêng theo từng cặp (CTV1 – CTV2)

### 3. Logic tạo commission khi Payment được xác nhận

**Khi Org xác nhận Payment:**

1. Tạo commission cho CTV cấp 1 (direct) đúng mức 1750k/750k
2. Nạp tiền vào ví của CTV cấp 1
3. Kiểm tra nếu Student đi theo ref_id của CTV cấp 2:
    - Lấy cấu hình từ CTV cấp 1
    - Tạo commission cho CTV cấp 2
    - Nếu "trả ngay" → commission trạng thái `payable`
    - Nếu "trả khi nhập học" → commission trạng thái `pending`

### 4. Logic cập nhật commission khi Student nhập học

**Khi Student được đánh dấu "nhập học":**

1. Tất cả commission `pending` của CTV cấp 2 đổi sang `payable`
2. Tự động chuyển tiền từ ví CTV cấp 1 sang ví CTV cấp 2
3. Lưu transaction giao dịch

### 5. Hệ thống ví tiền

**Chức năng:**

-   Org nạp vào ví của CTV cấp 1
-   Khi commission CTV cấp 2 đến hạn → trừ từ ví CTV cấp 1, cộng vào ví CTV cấp 2
-   Lưu transaction ai trả cho ai, số tiền, thời điểm

**Vị trí:** Admin > Quản lý hoa hồng > Ví tiền

### 6. Dashboard cập nhật theo 3 góc nhìn

#### Góc nhìn Organization (Super Admin):

-   Tổng đã chi cho CTV cấp 1
-   Commission đang chờ
-   Tổng commission đã tạo

#### Góc nhìn CTV cấp 1:

-   Số dư ví
-   Tổng nhận từ Org
-   Tổng đã chi cho tuyến dưới
-   Net còn lại

#### Góc nhìn CTV cấp 2:

-   Tổng được hưởng
-   Đã thanh toán
-   Đang chờ
-   Số dư ví

## Hướng dẫn sử dụng

### 1. Thiết lập quan hệ CTV

1. Vào **Collaborators** > **Edit**
2. Chọn **Upline** (CTV cha) cho CTV cấp 2
3. Lưu thay đổi

### 2. Cấu hình hoa hồng tuyến dưới

1. Vào **Cấu hình hoa hồng tuyến dưới**
2. Click **Thêm cấu hình mới**
3. Chọn CTV cấp 1 và CTV cấp 2
4. Nhập số tiền cho từng hệ
5. Chọn hình thức thanh toán
6. Lưu cấu hình

### 3. Xác nhận Payment

1. Vào **Payments**
2. Tìm payment cần xác nhận
3. Click **Xác nhận**
4. Hệ thống tự động tạo commission

### 4. Đánh dấu Student nhập học

1. Vào **Students**
2. Tìm student cần đánh dấu
3. Click **Đánh dấu nhập học**
4. Hệ thống tự động cập nhật commission

### 5. Quản lý ví tiền

1. Vào **Ví tiền**
2. Xem số dư và giao dịch
3. Click **Giao dịch** để xem chi tiết

## Các trạng thái Commission

-   **pending:** Chờ nhập học (chỉ áp dụng cho CTV cấp 2 với hình thức "trả khi nhập học")
-   **payable:** Có thể thanh toán
-   **paid:** Đã thanh toán

## Lưu ý quan trọng

1. **CTV cấp 1** chỉ có thể cấu hình hoa hồng cho **CTV cấp 2** của mình
2. **CTV cấp 2** chỉ được xem thông tin, không được chỉnh sửa
3. Hoa hồng tuyến dưới được trích từ phần của CTV cấp 1, không ảnh hưởng đến Org
4. Hệ thống tự động tạo ví cho tất cả CTV
5. Giao dịch ví được lưu đầy đủ để audit trail

## Troubleshooting

### Lỗi thường gặp:

1. **Commission không được tạo:**

    - Kiểm tra cấu hình hoa hồng tuyến dưới
    - Kiểm tra quan hệ CTV cha-con
    - Kiểm tra ref_id của student

2. **Ví không có tiền:**

    - Kiểm tra payment đã được xác nhận chưa
    - Kiểm tra commission đã được tạo chưa

3. **Commission không chuyển trạng thái:**
    - Kiểm tra student đã được đánh dấu nhập học chưa
    - Kiểm tra hình thức thanh toán trong cấu hình
