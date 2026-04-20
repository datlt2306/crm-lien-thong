# Design: Audit Log System

## 1. Data Schema

### Table: `audit_logs`
| Column | Type | Description |
| --- | --- | --- |
| `id` | `ulid` | Unique identifier (ordered) |
| `event_group` | `string` | `FINANCIAL`, `ACCOUNT_DELETION`, `SYSTEM` |
| `event_type` | `string` | `CREATED`, `UPDATED`, `DELETED`, `RESTORED`, `REVERTED` |
| `auditable_type` | `string` | Polymorphic model type |
| `auditable_id` | `unsignedBigInteger`| Polymorphic model ID |
| `user_id` | `foreignId` | Operator (User ID) |
| `user_role` | `string` | Snapshot of operator's role |
| `student_id` | `foreignId` | Related student (nullable, for filtering) |
| `old_values` | `json` | Data before change |
| `new_values` | `json` | Data after change |
| `amount_diff` | `decimal` | Numeric difference (for financial logs) |
| `reason` | `text` | Required reason for changes |
| `ip_address` | `string` | User IP |
| `user_agent` | `text` | User Agent |
| `metadata` | `json` | Snapshot JSON or extra context |
| `created_at` | `timestamp` | Immutable timestamp |

## 2. Architecture Components

### Trait: `HasAuditLog`
- Gắn vào các Model cần track log (`Payment`, `CommissionItem`, `User`, `Student`, `Collaborator`).
- Tự động hook vào các sự kiện Eloquent: `created`, `updated`, `deleted`.
- **Lưu ý**: Đối với sự kiện xóa, lưu Snapshot JSON vào `metadata`.

### Observer: `AuditLogObserver`
- (Có thể sử dụng Trait hoặc Observer riêng). Để tách biệt logic, tôi đề xuất dùng Trait để linh hoạt gán cho từng Model cụ thể.

### Central Log Service
- Chịu trách nhiệm ghi log để đảm bảo format đồng nhất.
- Hỗ trợ capture Request context (IP, User Agent).

## 3. Implementation Logic

### Financial Tracking
- Khi `Payment` hoặc `CommissionItem` thay đổi `amount` hoặc `status`:
    - Tính toán `amount_diff = new - old`.
    - Ghi log với `event_group = FINANCIAL`.

### Account Deletion Tracking
- Khi một Model (`User`, `Student`, `Collaborator`) bị xóa:
    - Capture toàn bộ `$model->toArray()` lưu vào `metadata`.
    - Ghi log với `event_group = ACCOUNT_DELETION`.

## 4. UI/UX in Filament
- **List View**: 
    - Hiển thị badge cho `event_group` (Màu xanh cho Financial, Màu đỏ cho Xóa).
    - Hiển thị Operator & Role.
- **Timeline View**: Sử dụng `Infolist` trong Filament để dựng Timeline.
- **Filters**:
    - Select Operator.
    - Date Range.
    - Event Group.
    - Student Search.

## 5. Security & Permissions
- **Immutable**: Không đăng ký route/action `edit` hoặc `delete` trong Filament Resource.
- **AuditLogPolicy**:
    - `viewAny`: Admin, Accountant, Admissions.
    - `view`: CTV (nếu `audit_log->student_id` thuộc quản lý của họ).
