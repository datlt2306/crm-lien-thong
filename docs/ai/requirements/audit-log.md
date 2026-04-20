# Requirements: Audit Log System

## Overview
Create a specialized Audit Log module for the CRM recruitment system to track critical financial changes and account deletions. This system serves as a legal-grade evidence log for reconciliation and transparency.

## 1. Event Groups

### Group 1: Financial Variations (Biến động tiền)
Ghi log khi **Tạo/Sửa/Xóa** các mục sau:
- **Học phí (Tuition)**: Gắn liền với Student hoặc Payment.
- **Hoa hồng CTV (Commission)**: Commission items.
- **Thanh toán (Payment)**: Các bản ghi Payment.
- **Hoàn tiền (Refund)**: Khi Payment chuyển sang trạng thái `reverted`.
- **Thượng/Phạt (Bonuses/Penaltires)**: Các giao dịch đặc biệt (WalletTransaction).

#### Mandatory Fields for Financial Logs:
- **Timestamp**: Thời gian thao tác.
- **Operator**: Người thực hiện (User).
- **Role**: Vai trò của người thực hiện tại thời điểm đó (Accountant, Admissions, Admin...).
- **Related Profile**: Liên kết đến hồ sơ Sinh viên liên quan.
- **Old Value**: Giá trị cũ.
- **New Value**: Giá trị mới.
- **Difference**: Chênh lệch (Old - New).
- **Reason**: Lý do chỉnh sửa (bắt buộc).
- **Context**: IP, Device, User-Agent.

### Group 2: Account Deletion (Xóa tài khoản)
Ghi log khi xóa các đối tượng:
- Collaborator (Cộng tác viên)
- Student (Sinh viên)
- Staff/Admin (Nhân viên/Admin)

#### Requirements for Deletion Logs:
- **Operator**: Ai thực hiện hành vi xóa.
- **Target**: Xóa ai (User/Student/Collaborator ID & Name).
- **Soft Delete**: Hệ thống phải sử dụng Soft Delete, không xóa cứng.
- **Snapshot JSON**: Lưu toàn bộ dữ liệu trước khi xóa vào trường JSON.
- **Reason**: Lý do xóa.

## 2. System Features
- **Access Control**:
    - CTV chỉ xem được log liên quan đến hồ sơ của họ.
    - Admin/Accountant xem toàn bộ.
- **User Interface**:
    - Filament Resource cho Audit Logs.
    - Timeline view (thanh thời gian dễ đọc).
    - Bộ lọc (Filter) theo: Ngày, User thao tác, Loại Log, Hồ sơ (Student).
- **Export**: Hỗ trợ xuất Excel/PDF.
- **Data Integrity**: **KHÔNG** cho phép sửa hoặc xóa Log. Log chỉ được thêm mới (Append-only).

## 3. Technical Stack
- Laravel 11 / Laravel 12
- Filament v3
- Observers / Eloquent Hooks
- Policies for Security
- Migration & Models
