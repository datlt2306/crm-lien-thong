# Tối ưu hóa hệ thống Commission - Loại bỏ trùng lặp

## Tổng quan vấn đề

Hệ thống hiện tại có nhiều chức năng trùng lặp về hoa hồng:

### Các Resource hiện tại:

1. **CommissionResource** - Quản lý commission items
2. **DownlineCommissionConfigResource** - Cấu hình hoa hồng tuyến dưới
3. **WalletResource** - Quản lý ví tiền
4. **WalletTransactionResource** - Giao dịch ví
5. **PaymentResource** - Thanh toán (có logic tạo commission)
6. **CommissionPolicyResource** - Chính sách hoa hồng

### Vấn đề trùng lặp:

-   Logic tạo commission phân tán ở nhiều nơi
-   Quản lý ví tiền và giao dịch riêng biệt
-   Cấu hình hoa hồng phức tạp và khó maintain
-   Thiếu audit trail cho các thay đổi

## Giải pháp tối ưu hóa

### 1. Tạo CommissionManagementService tổng hợp

**Mục đích:** Tập trung tất cả logic commission vào một service duy nhất

**Chức năng chính:**

-   Tạo commission từ payment
-   Quản lý ví tiền và giao dịch
-   Cập nhật commission khi student nhập học
-   Thống kê theo góc nhìn khác nhau

### 2. Cập nhật Database Schema

**Migration:** `2025_08_22_132624_optimize_commission_system_remove_duplicates.php`

**Thay đổi:**

-   Thêm index để tối ưu performance
-   Thêm trường `original_amount`, `notes` cho commission_items
-   Thêm trường `pending_balance`, `available_balance` cho wallets
-   Tạo bảng `commission_audit_logs` để audit trail

### 3. Tối ưu hóa Resource Structure

#### CommissionResource (Chính)

-   Quản lý tất cả commission items
-   Phân quyền theo role (super_admin, ctv, chủ đơn vị)
-   Actions: mark_payable, mark_paid, mark_cancelled
-   Filters: status, role, trigger

#### DownlineCommissionConfigResource (Cấu hình)

-   Cấu hình hoa hồng tuyến dưới
-   Chỉ CTV cấp 1 và super_admin có quyền
-   Quản lý hình thức thanh toán (trả ngay/trả khi nhập học)

#### WalletResource (Ví tiền)

-   Quản lý ví tiền của CTV
-   Hiển thị số dư, tổng nhận, tổng chi
-   Link đến giao dịch

#### PaymentResource (Thanh toán)

-   Quản lý thanh toán của sinh viên
-   Action verify để tạo commission tự động
-   Tích hợp với CommissionManagementService

### 4. Luồng nghiệp vụ tối ưu

#### Khi xác nhận Payment:

1. PaymentResource gọi CommissionManagementService
2. Service tạo commission cho CTV cấp 1 (payable ngay)
3. Service kiểm tra và tạo commission cho CTV cấp 2
4. Service cập nhật ví tiền và tạo giao dịch
5. Service ghi audit log

#### Khi student nhập học:

1. StudentResource gọi CommissionManagementService
2. Service tìm commission pending của CTV cấp 2
3. Service chuyển trạng thái sang payable
4. Service chuyển tiền từ ví CTV cấp 1 sang CTV cấp 2
5. Service ghi audit log

### 5. Phân quyền tối ưu

#### Super Admin:

-   Xem tất cả commission, ví, cấu hình
-   Quản lý toàn bộ hệ thống

#### Chủ đơn vị:

-   Xem commission, ví của tổ chức mình
-   Quản lý cấu hình hoa hồng của tổ chức

#### CTV cấp 1:

-   Xem commission của mình
-   Cấu hình hoa hồng cho CTV cấp 2
-   Quản lý ví tiền của mình

#### CTV cấp 2:

-   Chỉ xem commission của mình
-   Xem ví tiền của mình
-   Không có quyền chỉnh sửa

### 6. Dashboard tổng hợp

#### Góc nhìn Organization:

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

## Lợi ích sau tối ưu hóa

### 1. Performance

-   Index tối ưu cho các query phổ biến
-   Giảm số lượng database calls
-   Cache thống kê dashboard

### 2. Maintainability

-   Logic tập trung trong một service
-   Dễ debug và test
-   Audit trail đầy đủ

### 3. Scalability

-   Dễ thêm tính năng mới
-   Dễ mở rộng cho nhiều tổ chức
-   Dễ tích hợp với hệ thống khác

### 4. Security

-   Phân quyền rõ ràng theo role
-   Audit log cho mọi thay đổi
-   Validation chặt chẽ

## Hướng dẫn triển khai

### Bước 1: Chạy migration

```bash
php artisan migrate
```

### Bước 2: Cập nhật CommissionManagementService

-   Tạo file `app/Services/CommissionManagementService.php`
-   Implement tất cả logic commission

### Bước 3: Cập nhật Resources

-   Cập nhật CommissionResource với phân quyền
-   Tích hợp CommissionManagementService vào PaymentResource
-   Tối ưu hóa WalletResource

### Bước 4: Test

-   Test luồng tạo commission
-   Test luồng nhập học
-   Test phân quyền
-   Test audit log

### Bước 5: Deploy

-   Deploy migration
-   Deploy code mới
-   Monitor performance

## Monitoring và Maintenance

### Metrics cần theo dõi:

-   Số lượng commission được tạo/ngày
-   Thời gian xử lý commission
-   Số lượng giao dịch ví
-   Performance của các query

### Maintenance tasks:

-   Cleanup audit logs cũ (sau 1 năm)
-   Optimize database indexes
-   Monitor wallet balances
-   Backup dữ liệu commission

## Kết luận

Sau khi tối ưu hóa:

-   Hệ thống commission sẽ gọn gàng, dễ maintain
-   Performance được cải thiện đáng kể
-   Audit trail đầy đủ cho compliance
-   Dễ dàng mở rộng và phát triển tính năng mới
