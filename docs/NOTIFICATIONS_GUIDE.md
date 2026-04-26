# Hướng dẫn sử dụng hệ thống Notifications

## Tổng quan

Hệ thống notifications đã được tích hợp vào CRM với các tính năng:

-   ✅ **Email Notifications**: Gửi thông báo qua email
-   ✅ **In-App Notifications**: Hiển thị thông báo trong ứng dụng Filament
-   ✅ **Notification Preferences**: Cài đặt tùy chỉnh cho từng loại thông báo
-   ✅ **Smart Targeting**: Tự động gửi thông báo cho đúng người dùng

## Cấu trúc Database

### 1. Bảng `notifications`

-   Lưu trữ tất cả thông báo trong hệ thống
-   Sử dụng Laravel Notifications mặc định

### 2. Bảng `push_tokens` (đã gỡ bỏ)

-   ~~Lưu trữ FCM tokens cho push notifications~~
-   ~~Hỗ trợ đa nền tảng (Web, iOS, Android)~~

### 3. Bảng `notification_preferences`

-   Cài đặt tùy chỉnh cho từng user
-   Kiểm soát loại thông báo và kênh gửi

## Các loại thông báo

### 1. PaymentVerifiedNotification

-   **Kích hoạt**: Khi thanh toán được xác minh
-   **Người nhận**: CTV, chủ đơn vị, kế toán, super admin
-   **Channels**: Email, In-App

### 2. PaymentRejectedNotification

-   **Kích hoạt**: Khi thanh toán bị từ chối
-   **Người nhận**: CTV, chủ đơn vị, kế toán, super admin
-   **Channels**: Email, In-App

### 3. CommissionEarnedNotification

-   **Kích hoạt**: Khi CTV nhận được hoa hồng
-   **Người nhận**: CTV nhận hoa hồng
-   **Channels**: Email, In-App

### 4. QuotaWarningNotification

-   **Kích hoạt**: Khi chỉ tiêu sắp hết
-   **Người nhận**: Super admin, chủ đơn vị
-   **Channels**: Email, In-App

## Cách sử dụng

### 1. Gửi thông báo thủ công

```php
use App\Services\NotificationService;
use App\Models\Payment;

$notificationService = app(NotificationService::class);

// Gửi thông báo thanh toán được xác minh
$payment = Payment::find(1);
$notificationService->notifyPaymentVerified($payment);

// Gửi thông báo thanh toán bị từ chối
$notificationService->notifyPaymentRejected($payment, 'Thiếu thông tin');

// Gửi thông báo hoa hồng
$commissionItem = CommissionItem::find(1);
$notificationService->notifyCommissionEarned($commissionItem);

// Gửi cảnh báo chỉ tiêu
$notificationService->notifyQuotaWarning('Công nghệ thông tin', 5, 100, 1);
```

### 2. Sử dụng Events (Khuyến nghị)

```php
use App\Events\PaymentVerified;
use App\Models\Payment;

// Kích hoạt event khi thanh toán được xác minh
$payment = Payment::find(1);
event(new PaymentVerified($payment));
```

### 3. Push Notifications (đã gỡ bỏ)

~~Push notifications đã được gỡ bỏ khỏi hệ thống.~~

## Cấu hình Environment

Không cần cấu hình thêm cho notifications (đã gỡ bỏ real-time và push).

## Filament Admin Interface

### 1. Notification Preferences

-   **Đường dẫn**: `/admin/notification-preferences`
-   **Chức năng**: Quản lý cài đặt thông báo cho từng user
-   **Quyền**: Super admin

### 2. Push Tokens (đã gỡ bỏ)

-   ~~**Đường dẫn**: `/admin/push-tokens`~~
-   ~~**Chức năng**: Quản lý FCM tokens~~
-   ~~**Quyền**: Super admin~~

### 3. Dashboard Widgets

-   **NotificationsWidget**: Thống kê tổng quan
-   **RecentNotificationsWidget**: Danh sách thông báo gần đây

## JavaScript/Frontend Integration

### 1. JavaScript/Frontend Integration (đã gỡ bỏ)

~~Push notifications và real-time notifications đã được gỡ bỏ khỏi hệ thống.~~

## Testing

### 1. Test gửi thông báo

```php
use Tests\TestCase;
use App\Services\NotificationService;
use App\Models\Payment;

class NotificationTest extends TestCase
{
    public function test_payment_verified_notification()
    {
        $payment = Payment::factory()->create();
        $notificationService = app(NotificationService::class);

        $notificationService->notifyPaymentVerified($payment);

        // Assert notifications were sent
        $this->assertDatabaseHas('notifications', [
            'type' => PaymentVerifiedNotification::class,
        ]);
    }
}
```

### 2. Test Push Notifications (đã gỡ bỏ)

~~Push notification tests đã được gỡ bỏ.~~

## Monitoring và Debugging

### 1. Log Files

-   Thông báo lỗi: `storage/logs/laravel.log`
-   ~~Push notification errors: Tìm kiếm "Failed to send push notification"~~

### 2. Database Queries

```sql
-- Xem thống kê notifications
SELECT type, COUNT(*) as count
FROM notifications
GROUP BY type;

-- Xem push tokens hoạt động (đã gỡ bỏ)
-- SELECT platform, COUNT(*) as count
-- FROM push_tokens
-- WHERE is_active = 1
-- GROUP BY platform;

-- Xem preferences
SELECT
    u.name,
    np.email_payment_verified,
    np.push_payment_verified,
    np.in_app_payment_verified
FROM notification_preferences np
JOIN users u ON np.user_id = u.id;
```

## Best Practices

1. **Luôn sử dụng Events**: Thay vì gọi trực tiếp notification service
2. **Kiểm tra preferences**: Luôn kiểm tra user có muốn nhận thông báo không
3. **Handle errors**: Xử lý lỗi khi gửi push notifications
4. **Cleanup tokens**: Vô hiệu hóa tokens không hợp lệ
5. **Rate limiting**: Giới hạn số lượng thông báo gửi
6. **Testing**: Test kỹ trước khi deploy

## Troubleshooting

### 1. Push notifications không hoạt động

-   Kiểm tra Firebase configuration
-   Verify FCM server key
-   Check token validity

### 2. Real-time notifications không hiển thị

-   Kiểm tra Pusher configuration
-   Verify WebSocket connection
-   Check authentication

### 3. Email không gửi được

-   Kiểm tra mail configuration
-   Verify SMTP settings
-   Check queue workers

## Support

Nếu gặp vấn đề, hãy kiểm tra:

1. Log files trong `storage/logs/`
2. Database tables có dữ liệu không
3. Environment configuration
4. Queue workers đang chạy không
