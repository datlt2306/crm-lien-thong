# Bài 1: Hướng dẫn dành cho Cộng tác viên (CTV)

Tài liệu này hướng dẫn CTV cách dùng mã giới thiệu, theo dõi học viên và kiểm tra hoa hồng trên hệ thống.

## 1. Cách lấy link giới thiệu

Sau khi tài khoản CTV được tạo, bạn sẽ có ít nhất một mã giới thiệu.

- Link tuyển sinh có dạng: `https://<ten-mien>/ref/<MA_GIOI_THIEU>`
- Ví dụ: `https://crm.tuyensinh.edu.vn/ref/LETRONGDAT`

Khi học viên đăng ký qua link này, hệ thống sẽ ghi nhận nguồn giới thiệu tương ứng.

## 2. Thời gian giữ lead

- Mặc định hệ thống giữ lead cho CTV trong **14 ngày**.
- Trong thời gian này, hồ sơ vẫn được gắn với CTV đã giới thiệu trước đó.
- Thời gian này có thể được quản trị viên thay đổi trong cấu hình hệ thống.

## 3. Theo dõi danh sách học viên

Sau khi đăng nhập, bạn có thể xem danh sách học viên thuộc nguồn của mình.

Các thông tin thường cần theo dõi:

- Học viên mới đăng ký
- Học viên đã gửi minh chứng chuyển khoản
- Học viên đã được xác minh thanh toán
- Học viên đã hoàn tất nhập học

## 4. Theo dõi hoa hồng

Hệ thống hiển thị các dòng hoa hồng phát sinh theo từng học viên.

Các trạng thái chính:

- **Chờ xử lý**: Chưa đến điều kiện chi trả, thường dùng cho khoản chờ nhập học
- **Có thể thanh toán**: Đã đủ điều kiện để kế toán đối soát và thanh toán
- **Đã thanh toán** hoặc **Đã chốt & Đã chi**: Đã được xử lý thanh toán
- **CTV đã nhận tiền**: Đã xác nhận ở bước đối soát hoặc điều chỉnh nghiệp vụ

## 5. Cách hiểu thời điểm nhận hoa hồng

Tùy chính sách áp dụng, hoa hồng có thể được mở theo một trong hai mốc:

- **Sau khi nộp phí**: Khi kế toán đã xác nhận học viên nộp tiền thành công
- **Sau khi sinh viên nhập học thực tế**: Khi học viên đã được cập nhật là nhập học

## 6. Nhận thông báo Telegram

CTV có thể nhận thông báo Telegram nếu đã khai báo đúng ID Telegram trong hồ sơ cá nhân.

Hệ thống hiện hỗ trợ các thông báo như:

- Có học viên đăng ký mới
- Có minh chứng chuyển khoản mới
- Thanh toán đã được xác nhận
- Phát sinh hoa hồng mới

## 7. CTV chính và CTV phụ nhận gì trên Telegram

Hệ thống hiện phân biệt hai nhóm:

- **CTV chính**: là tài khoản cộng tác viên chính
- **CTV phụ**: là nguồn phụ được tạo thêm trong phần **Quản lý nguồn CTV Phụ**

### CTV chính nhận được gì

CTV chính có thể nhận:

- Thông báo có học viên đăng ký mới
- Thông báo có minh chứng chuyển khoản mới
- Thông báo thanh toán đã được xác nhận
- Thông báo phát sinh hoa hồng mới
- Báo cáo tổng hợp khi dùng lệnh kiểm tra trên Telegram

Báo cáo tổng hợp của CTV chính hiện gồm:

- Tổng số hồ sơ
- Tổng số tiền cộng dồn theo các hồ sơ đang thuộc mình và các nguồn phụ
- Phần trực tiếp của chính mình
- Phần tách riêng theo từng nguồn phụ

### CTV phụ nhận được gì

CTV phụ nhận thông tin theo đúng mã nguồn phụ của mình.

Khi dùng lệnh kiểm tra, CTV phụ sẽ thấy:

- Số lượng hồ sơ theo từng hệ đào tạo
- Tổng số hồ sơ của nguồn đó
- Danh sách 5 hồ sơ mới nhất
- Ghi chú thời điểm quyết toán theo từng hệ

CTV phụ không nhận báo cáo tổng hợp toàn bộ mạng lưới của CTV chính.

## 8. Gửi bill qua Telegram

Ngoài cách gửi bill trên web, hệ thống còn cho phép gửi bill nhanh qua Telegram.

Cách dùng:

1. Mở tin nhắn Telegram thông báo học viên mới.
2. Bấm **trả lời** đúng tin nhắn đó.
3. Gửi ảnh bill chuyển khoản hoặc file ảnh bill.

Lưu ý:

- Hệ thống chỉ nhận khi bạn **trả lời đúng tin nhắn thông báo hồ sơ**
- Nếu hồ sơ đã được xác nhận thanh toán, hệ thống sẽ từ chối nhận thêm bill
- Nếu gửi file không phải ảnh, hệ thống sẽ báo lỗi

## 9. Lưu ý khi sử dụng

- Luôn gửi đúng link có chứa mã giới thiệu của bạn.
- Kiểm tra kỹ số điện thoại học viên vì đây là dữ liệu dễ dùng để đối chiếu.
- Nếu chưa nhận được thông báo Telegram, cần kiểm tra lại ID Telegram trong hồ sơ cá nhân.
