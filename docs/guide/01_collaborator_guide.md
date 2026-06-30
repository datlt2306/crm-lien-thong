# Bài 1: Hướng dẫn dành cho Cộng tác viên (CTV)

Chào mừng bạn đến với hệ thống tuyển sinh liên thông GTVT. Tài liệu này hướng dẫn bạn cách sử dụng link giới thiệu để tuyển học sinh và theo dõi số tiền hoa hồng được nhận trực tiếp trên hệ thống.

---

## 1. Đăng ký tài khoản CTV mới
1.  Truy cập vào trang đăng ký dành cho CTV.
2.  Điền đầy đủ thông tin: Họ tên, Số điện thoại, Email, Số tài khoản ngân hàng và Tên ngân hàng của bạn.
3.  Nhấn nút gửi yêu cầu. Cán bộ quản lý của trường sẽ xem xét, phê duyệt tài khoản và cấp cho bạn một **Mã giới thiệu** (Ví dụ: `LETRONGDAT`).

![Hình 1.1: Ảnh minh họa mẫu điền đơn đăng ký tài khoản CTV mới](images/ctv_registration_form.png)

---

## 2. Cách lấy Link giới thiệu để gửi cho học sinh
Sau khi có Mã giới thiệu, bạn sẽ có một đường link tuyển sinh riêng. Hãy gửi link này cho những học sinh quan tâm đến khóa học:

*   **Công thức đường link**: `https://<ten-mien-he-thong>/ref/<MA_CTV_CUA_BAN>`
    *   *Ví dụ*: `https://crm.tuyensinh.edu.vn/ref/LETRONGDAT`
*   **Cách thức hoạt động**: Khi học sinh click vào link này, hệ thống sẽ tự động ghi nhớ bạn là người giới thiệu của học sinh đó trong vòng **30 ngày**. Dù học sinh không đăng ký ngay lúc đó, nhưng trong vòng 30 ngày tiếp theo nếu họ quay lại trang web đăng ký học, hệ thống vẫn ghi nhận học sinh đó thuộc về bạn giới thiệu.

![Hình 1.2: Ảnh minh họa giao diện sao chép Link giới thiệu tuyển sinh của CTV](images/ctv_referral_link.png)

---

## 3. Theo dõi danh sách học sinh của mình
Bạn đăng nhập vào trang cá nhân của CTV để xem danh sách học sinh mà mình đã giới thiệu cùng trạng thái hiện tại của họ:
*   **Đăng ký mới**: Học sinh vừa điền đơn đăng ký học xong, chưa nộp tiền.
*   **Đã nộp hóa đơn**: Học sinh đã chuyển tiền học phí/lệ phí thành công và đã tải ảnh biên lai gửi tiền lên web.
*   **Đã nhập học**: Học sinh đã hoàn tất nộp hồ sơ giấy và được trường GTVT phê duyệt trúng tuyển chính thức.

![Hình 1.3: Ảnh minh họa bảng danh sách học sinh đăng ký qua mã giới thiệu](images/ctv_student_list.png)

---

## 4. Theo dõi & Đối soát tiền hoa hồng

Hệ thống cung cấp trang **Danh sách hoa hồng** trên trang cá nhân của bạn để quản lý và theo dõi dòng tiền:

### Các trạng thái của khoản hoa hồng:
*   **Chờ nhập học (Pending)**: Khoản tiền hoa hồng phát sinh từ hồ sơ của học sinh nhưng chưa đến hạn nhận (Ví dụ: Đợt 2 của hệ VHVL/Từ xa chỉ được mở khóa khi học sinh nộp đủ hồ sơ giấy và chính thức nhập học).
*   **Có thể thanh toán (Payable)**: Khoản hoa hồng đã đủ điều kiện nhận. Nhà trường sẽ tự động đối soát danh sách này để chuyển khoản cho bạn.
*   **Đã thanh toán (Paid)**: Khoản hoa hồng nhà trường đã chuyển khoản thành công vào tài khoản ngân hàng của bạn và đính kèm biên lai chuyển tiền.

![Hình 1.4: Ảnh minh họa giao diện Danh sách hoa hồng và các trạng thái thanh toán của CTV](images/ctv_wallet_dashboard.png)

### Quy trình nhận tiền:
1. Bạn không cần làm lệnh rút tiền trên web.
2. Nhà trường sẽ định kỳ đối soát các khoản hoa hồng có trạng thái **Có thể thanh toán** trên hệ thống.
3. Kế toán trường thực hiện chuyển khoản trực tiếp vào số tài khoản ngân hàng của bạn và cập nhật trạng thái dòng hoa hồng sang **Đã thanh toán** kèm ảnh biên lai giao dịch thành công. Bạn sẽ nhận được thông báo biến động số dư qua Telegram Bot.
