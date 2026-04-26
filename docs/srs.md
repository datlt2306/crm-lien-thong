# SOFTWARE REQUIREMENTS SPECIFICATION (SRS)

## Hệ thống CRM Quản lý Cộng tác viên và Học viên Liên thông

### 1. TỔNG QUAN HỆ THỐNG

#### 1.1 Mục đích

Hệ thống CRM được phát triển để quản lý quy trình tuyển sinh liên thông đại học, bao gồm:

-   Quản lý cộng tác viên (CTV) theo cấp bậc
-   Quản lý học viên và quy trình đăng ký
-   Xử lý thanh toán và hoa hồng
-   Quản lý tổ chức và chương trình đào tạo

#### 1.2 Phạm vi

-   Hệ thống web-based sử dụng Laravel Framework
-   Giao diện quản trị sử dụng Filament Admin Panel
-   Hệ thống phân quyền dựa trên vai trò người dùng
-   Tích hợp xử lý thanh toán và tính toán hoa hồng tự động

#### 1.3 Công nghệ sử dụng

-   **Backend**: Laravel 12.x, PHP 8.2+
-   **Frontend**: Filament 4.x, TailwindCSS
-   **Database**: SQLite (development), MySQL/PostgreSQL (production)
-   **Authentication**: Laravel Auth + Spatie Permission
-   **File Storage**: Laravel Storage

### 2. CÁC VAI TRÒ NGƯỜI DÙNG

#### 2.1 Super Admin

-   **Quyền hạn**: Toàn quyền truy cập hệ thống
-   **Chức năng chính**:
    -   Quản lý tất cả tổ chức, CTV, học viên
    -   Cấu hình chính sách hoa hồng
    -   Xem báo cáo tổng hợp
    -   Quản lý người dùng hệ thống

#### 2.2 Chủ đơn vị

-   **Quyền hạn**: Quản lý tổ chức của mình
-   **Chức năng chính**:
    -   Xem danh sách CTV cấp 1 trong tổ chức
    -   Xác nhận thanh toán học viên
    -   Quản lý chương trình và ngành học
    -   Xem báo cáo tài chính

#### 2.3 Cộng tác viên (CTV)

-   **Quyền hạn**: Quản lý tuyến dưới và học viên
-   **Chức năng chính**:
    -   Xem danh sách CTV cấp 2 (nếu có)
    -   Quản lý học viên của mình
    -   Xem ví tiền và hoa hồng
    -   Tạo link giới thiệu

#### 2.4 Kế toán

-   **Quyền hạn**: Xử lý thanh toán và hóa đơn
-   **Chức năng chính**:
    -   Xem danh sách thanh toán
    -   Upload phiếu thu sau khi xác nhận thanh toán

### 3. CẤU TRÚC DỮ LIỆU

#### 3.1 Các Entity chính

**User (Người dùng)**

-   id, name, email, phone, avatar, password, role
-   Vai trò: super_admin, chủ đơn vị, ctv, kế toán

**Organization (Tổ chức)**

-   id, name, code, contact_name, contact_phone, status, owner_id
-   Quan hệ: hasMany Collaborators, hasMany Students

**Collaborator (Cộng tác viên)**

-   id, full_name, phone, email, organization_id, ref_id, upline_id, note, status
-   Quan hệ: belongsTo Organization, belongsTo upline, hasMany downlines

**Student (Học viên)**

-   id, full_name, phone, email, organization_id, collaborator_id, target_university, major, intake_month, program_type, source, status, notes, dob, address
-   Trạng thái: new, contacted, submitted, approved, enrolled, rejected

**Payment (Thanh toán)**

-   id, organization_id, student_id, primary_collaborator_id, sub_collaborator_id, program_type, amount, bill_path, receipt_path, status, verified_by, verified_at
-   Trạng thái: SUBMITTED, VERIFIED, REJECTED

**Commission (Hoa hồng)**

-   id, organization_id, payment_id, student_id, rule, generated_at
-   Quan hệ: hasMany CommissionItems

**CommissionItem (Chi tiết hoa hồng)**

-   id, commission_id, recipient_collaborator_id, role, amount, status, trigger, payable_at, visibility, meta
-   Trạng thái: PENDING, PAYABLE, PAYMENT_CONFIRMED, COMPLETED

**Wallet (Ví tiền)**

-   id, collaborator_id, balance, status
-   Quan hệ: hasMany WalletTransactions

**Major (Ngành học)**

-   id, code, name, is_active
-   Quan hệ: belongsToMany Organizations

**Program (Chương trình)**

-   id, code, name, is_active, direct_commission_amount
-   Quan hệ: belongsToMany Organizations

#### 3.2 Quan hệ dữ liệu

-   Organization → hasMany Collaborators
-   Collaborator → belongsTo upline, hasMany downlines
-   Student → belongsTo Collaborator, Organization
-   Payment → belongsTo Student, Organization, Collaborators
-   Commission → hasMany CommissionItems
-   Wallet → belongsTo Collaborator

### 4. LUỒNG HOẠT ĐỘNG CHO TỪNG ACTOR

#### 4.1 Super Admin

**4.1.1 Luồng quản lý hệ thống**

1. **Đăng nhập hệ thống**

    - Truy cập admin panel
    - Xem dashboard tổng quan
    - Kiểm tra thống kê hệ thống

2. **Quản lý tổ chức**

    - Tạo/sửa/xóa tổ chức
    - Gán chủ đơn vị cho tổ chức
    - Cấu hình chương trình và ngành học
    - Phân bổ chỉ tiêu theo ngành

3. **Quản lý người dùng**

    - Tạo tài khoản cho các vai trò
    - Phân quyền chi tiết
    - Reset mật khẩu khi cần
    - Theo dõi hoạt động người dùng

4. **Cấu hình hệ thống**
    - Thiết lập chính sách hoa hồng
    - Cấu hình thông báo
    - Quản lý file và backup
    - Monitor hiệu suất hệ thống

**4.1.2 Luồng xử lý báo cáo**

1. **Tạo báo cáo tổng hợp**

    - Thống kê doanh thu theo tổ chức
    - Báo cáo hiệu suất CTV
    - Phân tích nguồn tuyển sinh
    - Xuất dữ liệu Excel/PDF

2. **Theo dõi hoạt động**
    - Monitor đăng ký học viên real-time
    - Theo dõi thanh toán và hoa hồng
    - Cảnh báo bất thường
    - Audit log hệ thống

#### 4.2 Chủ đơn vị

**4.2.1 Luồng quản lý tổ chức**

1. **Thiết lập ban đầu**

    - Đăng nhập với tài khoản được cấp
    - Cập nhật thông tin tổ chức
    - Cấu hình chương trình đào tạo
    - Thiết lập ngành học và chỉ tiêu

2. **Quản lý CTV cấp 1**

    - Xem danh sách CTV cấp 1 trong tổ chức
    - Phê duyệt đăng ký CTV mới
    - Theo dõi hiệu suất CTV
    - Cập nhật thông tin CTV

3. **Quản lý học viên**
    - Xem danh sách học viên đã nộp tiền
    - Theo dõi pipeline học viên
    - Cập nhật trạng thái học viên
    - Xử lý hồ sơ học viên

**4.2.2 Luồng xử lý thanh toán**

1. **Xác nhận thanh toán**

    - Nhận thông báo có thanh toán mới
    - Xem chi tiết hóa đơn học viên
    - Xác minh thông tin thanh toán
    - Xác nhận → chuyển status VERIFIED

2. **Quản lý tài chính**
    - Theo dõi doanh thu theo thời gian
    - Xem báo cáo thanh toán
    - Quản lý hoa hồng CTV
    - Xuất báo cáo tài chính

#### 4.3 Cộng tác viên (CTV)

**4.3.1 Luồng tuyển sinh học viên**

1. **Tạo link giới thiệu**

    - Đăng nhập hệ thống
    - Lấy link ref_id của mình
    - Chia sẻ link qua các kênh marketing
    - Theo dõi hiệu quả link

2. **Hỗ trợ học viên đăng ký**

    - Hướng dẫn học viên điền form
    - Thu thập thông tin học viên
    - Theo dõi trạng thái đăng ký
    - Hỗ trợ nộp hóa đơn thanh toán

3. **Quản lý học viên**
    - Xem danh sách học viên của mình
    - Cập nhật thông tin học viên
    - Theo dõi pipeline từ đăng ký đến nhập học
    - Liên hệ hỗ trợ học viên

**4.3.2 Luồng quản lý tuyến dưới**

1. **Tuyển CTV cấp 2**

    - Chia sẻ link đăng ký CTV
    - Hướng dẫn CTV mới đăng ký
    - Phê duyệt đăng ký CTV cấp 2
    - Đào tạo và hỗ trợ CTV mới

2. **Quản lý CTV cấp 2**
    - Xem danh sách CTV cấp 2 (nếu có)
    - Theo dõi hiệu suất CTV cấp 2
    - Hỗ trợ và đào tạo CTV
    - Quản lý hoa hồng cho CTV cấp 2

**4.3.3 Luồng quản lý hoa hồng**

1. **Theo dõi hoa hồng**

    - Xem ví tiền và số dư
    - Theo dõi hoa hồng từ học viên
    - Xem lịch sử giao dịch
    - Kiểm tra trạng thái thanh toán

2. **Xử lý hoa hồng CTV cấp 2**
    - Nhận thông báo hoa hồng cho CTV cấp 2
    - Xác nhận đã chuyển tiền cho CTV cấp 2
    - Upload hóa đơn chuyển tiền
    - Theo dõi trạng thái thanh toán

#### 4.4 Kế toán

**4.4.1 Luồng xử lý thanh toán**

1. **Theo dõi thanh toán**

    - Xem danh sách thanh toán đã xác nhận
    - Kiểm tra thông tin thanh toán
    - Theo dõi trạng thái xử lý
    - Liên hệ với chủ đơn vị khi cần

2. **Upload phiếu thu**
    - Nhận thông báo thanh toán được xác nhận
    - Tải lên phiếu thu tương ứng
    - Liên kết phiếu thu với Payment
    - Cập nhật trạng thái hoàn thành

**4.4.2 Luồng quản lý tài chính**

1. **Theo dõi tài chính**

    - Xem báo cáo thanh toán theo thời gian
    - Theo dõi doanh thu tổ chức
    - Kiểm tra tính chính xác dữ liệu
    - Xuất báo cáo tài chính

2. **Xử lý hóa đơn**
    - Quản lý file hóa đơn và phiếu thu
    - Đảm bảo tính bảo mật file
    - Backup dữ liệu tài chính
    - Audit trail cho các giao dịch

#### 4.5 Học viên (End User)

**4.5.1 Luồng đăng ký học viên**

1. **Tìm hiểu chương trình**

    - Nhận link giới thiệu từ CTV
    - Truy cập form đăng ký `/ref/{ref_id}`
    - Đọc thông tin chương trình
    - Liên hệ CTV để được tư vấn

2. **Điền form đăng ký**

    - Nhập thông tin cá nhân
    - Chọn ngành học và trường đích
    - Cung cấp thông tin liên hệ
    - Submit form đăng ký

3. **Nộp hóa đơn thanh toán**
    - Nhận hướng dẫn từ CTV
    - Truy cập form thanh toán `/ref/{ref_id}/payment`
    - Upload hóa đơn thanh toán
    - Chờ xác nhận từ tổ chức

**4.5.2 Luồng theo dõi hồ sơ**

1. **Cập nhật thông tin**

    - Liên hệ CTV để cập nhật thông tin
    - Bổ sung giấy tờ cần thiết
    - Theo dõi tiến độ xử lý hồ sơ
    - Nhận thông báo từ hệ thống

2. **Hoàn thiện hồ sơ**
    - Cung cấp thông tin bổ sung
    - Nộp giấy tờ theo yêu cầu
    - Tham gia phỏng vấn (nếu có)
    - Nhận kết quả tuyển sinh

### 5. CHỨC NĂNG HỆ THỐNG

#### 4.1 Quản lý Cộng tác viên

**4.1.1 Đăng ký CTV**

-   Form đăng ký công khai tại `/ctv/register`
-   Tự động gán vào tổ chức của CTV giới thiệu
-   Tạo ref_id duy nhất 8 ký tự
-   Gửi thông báo xác nhận

**4.1.2 Phân cấp CTV**

-   CTV cấp 1: Không có upline (upline_id = null)
-   CTV cấp 2: Có upline là CTV cấp 1
-   Hiển thị menu theo cấp:
    -   Chủ đơn vị: Xem CTV cấp 1
    -   CTV cấp 1: Xem CTV cấp 2 (nếu có)
    -   CTV không có tuyến dưới: Ẩn menu

**4.1.3 Quản lý CTV**

-   CRUD operations cho CTV
-   Theo dõi trạng thái: active/inactive
-   Quản lý thông tin liên hệ
-   Tạo link giới thiệu

#### 4.2 Quản lý Học viên

**4.2.1 Đăng ký học viên**

-   Form đăng ký công khai tại `/ref/{ref_id}`
-   Tự động gán CTV từ ref_id
-   Thu thập thông tin: họ tên, SĐT, email, ngành học, trường đích
-   Theo dõi nguồn tuyển sinh

**4.2.2 Pipeline học viên**

-   Trạng thái: new → contacted → submitted → approved → enrolled
-   Cập nhật trạng thái theo quy trình
-   Gửi thông báo khi thay đổi trạng thái

**4.2.3 Quản lý học viên**

-   Tìm kiếm và lọc theo nhiều tiêu chí
-   Xem chi tiết thông tin học viên
-   Cập nhật trạng thái và ghi chú
-   Theo dõi lịch sử thay đổi

#### 4.3 Hệ thống Thanh toán

**4.3.1 Quy trình thanh toán**

1. Học viên nộp hóa đơn qua form `/ref/{ref_id}/payment`
2. Hệ thống tạo Payment với status SUBMITTED
3. Chủ đơn vị xác nhận → status VERIFIED
4. Tự động tạo Commission và CommissionItems

**4.3.2 Quản lý thanh toán**

-   Xem danh sách thanh toán theo trạng thái
-   Upload và xem hóa đơn
-   Xác nhận thanh toán
-   Theo dõi lịch sử thay đổi

**4.3.3 Upload phiếu thu**

-   Kế toán upload phiếu thu sau khi xác nhận
-   Lưu trữ file an toàn
-   Liên kết với Payment record

#### 4.4 Hệ thống Hoa hồng

**4.4.1 Tính toán hoa hồng**

-   CTV cấp 1: Nhận hoa hồng trực tiếp từ học viên
-   CTV cấp 2: Nhận hoa hồng từ CTV cấp 1
-   Sử dụng CommissionPolicy để tính toán
-   Hỗ trợ các loại: FIXED, PERCENT, PASS_THROUGH

**4.4.2 Chính sách hoa hồng**

-   Cấu hình theo chương trình (REGULAR, PART_TIME)
-   Cấu hình theo vai trò (PRIMARY, DOWNLINE)
-   Trigger: PAYMENT_VERIFIED, STUDENT_ENROLLED
-   Ưu tiên và hiệu lực thời gian

**4.4.3 Quản lý hoa hồng**

-   Tự động tạo khi Payment được xác nhận
-   Theo dõi trạng thái: PENDING → PAYABLE → PAYMENT_CONFIRMED → COMPLETED
-   Tính toán số tiền theo chính sách
-   Lịch sử giao dịch hoa hồng

#### 4.5 Hệ thống Ví tiền

**4.5.1 Quản lý ví**

-   Mỗi CTV có một ví riêng
-   Theo dõi số dư hiện tại
-   Lịch sử giao dịch chi tiết

**4.5.2 Giao dịch ví**

-   Nạp tiền từ hoa hồng
-   Rút tiền (nếu có)
-   Chuyển tiền giữa các ví
-   Theo dõi trạng thái giao dịch

#### 4.6 Quản lý Tổ chức

**4.6.1 Thông tin tổ chức**

-   Tên, mã, thông tin liên hệ
-   Chủ sở hữu (owner)
-   Trạng thái hoạt động

**4.6.2 Chương trình và Ngành học**

-   Quản lý chương trình đào tạo
-   Quản lý ngành học
-   Phân bổ chỉ tiêu theo ngành
-   Cấu hình hoa hồng theo chương trình

### 5. GIAO DIỆN NGƯỜI DÙNG

#### 5.1 Admin Panel (Filament)

-   **Dashboard**: Tổng quan hệ thống, thống kê
-   **Quản lý dữ liệu**: Users, Organizations, Collaborators, Students, Majors, Programs
-   **Thanh toán & Hoa hồng**: Payments, Commissions, Wallets
-   **Báo cáo**: Thống kê, biểu đồ, xuất dữ liệu

#### 5.2 Giao diện công khai

-   **Form đăng ký học viên**: `/ref/{ref_id}`
-   **Form thanh toán**: `/ref/{ref_id}/payment`
-   **Form đăng ký CTV**: `/ctv/register`
-   **Form đăng ký CTV qua ref**: `/ref/{ref_id}/ctv`

#### 5.3 Responsive Design

-   Tối ưu cho mobile và desktop
-   Sử dụng TailwindCSS
-   Giao diện thân thiện, dễ sử dụng

### 6. BẢO MẬT VÀ PHÂN QUYỀN

#### 6.1 Authentication

-   Laravel Auth system
-   Session-based authentication
-   Password hashing với bcrypt

#### 6.2 Authorization

-   Spatie Permission package
-   Role-based access control
-   Policy-based authorization
-   Gate definitions cho các chức năng

#### 6.3 Data Security

-   Input validation và sanitization
-   SQL injection prevention
-   XSS protection
-   CSRF protection
-   File upload security

### 7. TÍNH NĂNG NÂNG CAO

#### 7.1 Tracking và Analytics

-   Theo dõi nguồn tuyển sinh
-   Thống kê hiệu suất CTV
-   Báo cáo tài chính
-   Dashboard với biểu đồ

#### 7.2 Notification System

-   Email notifications
-   In-app notifications
-   Status change alerts
-   Commission notifications

#### 7.3 File Management

-   Upload và lưu trữ hóa đơn
-   Upload phiếu thu
-   Secure file access
-   File type validation

#### 7.4 Quota Management

-   Quản lý chỉ tiêu theo ngành
-   Tự động giảm quota khi có thanh toán
-   Cảnh báo khi hết quota
-   Theo dõi quota usage

### 8. TÍNH NĂNG KỸ THUẬT

#### 8.1 Performance

-   Database indexing
-   Query optimization
-   Caching strategies
-   Lazy loading relationships

#### 8.2 Scalability

-   Modular architecture
-   Service layer pattern
-   Repository pattern
-   Event-driven architecture

#### 8.3 Monitoring

-   Error logging
-   Performance monitoring
-   Database query logging
-   User activity tracking

#### 8.4 Testing

-   Unit tests với Pest
-   Feature tests
-   Database testing
-   API testing

### 9. DEPLOYMENT VÀ MAINTENANCE

#### 9.1 Environment

-   Development: SQLite database
-   Production: MySQL/PostgreSQL
-   Environment configuration
-   Secret management

#### 9.2 Deployment

-   Laravel deployment best practices
-   Database migrations
-   Asset compilation
-   Queue processing

#### 9.3 Backup và Recovery

-   Database backup
-   File storage backup
-   Disaster recovery plan
-   Data integrity checks

### 10. ROADMAP VÀ PHÁT TRIỂN

#### 10.1 Tính năng sắp tới

-   API RESTful cho mobile app
-   Real-time notifications
-   Advanced reporting
-   Multi-language support

#### 10.2 Cải tiến

-   Performance optimization
-   UI/UX improvements
-   Security enhancements
-   Integration capabilities

---

**Phiên bản**: 1.0  
**Ngày tạo**: 2024  
**Tác giả**: Development Team  
**Trạng thái**: Production Ready
