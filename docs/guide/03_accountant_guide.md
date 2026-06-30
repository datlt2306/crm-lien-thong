# Bài 3: Hướng dẫn dành cho Bộ phận Kế toán

Tài liệu này hướng dẫn kế toán xác minh thanh toán, theo dõi hoa hồng và xử lý điều chỉnh phát sinh.

## 1. Xác minh thanh toán của học viên

Khi học viên tải minh chứng chuyển khoản lên hệ thống, bản ghi thanh toán sẽ xuất hiện trong danh sách cần xử lý.

Các bước cơ bản:

1. Mở danh sách **Thanh toán**.
2. Kiểm tra thông tin học viên, số tiền và minh chứng đi kèm.
3. Đối chiếu với giao dịch thực tế.
4. Cập nhật **Trạng thái** thanh toán phù hợp.

Khi thanh toán được xác minh:

- Hệ thống ghi nhận trạng thái **Đã xác nhận**
- Hệ thống cập nhật phần chỉ tiêu liên quan
- Hệ thống tính hoa hồng theo chính sách đang áp dụng

## 2. Đối soát hoa hồng

Kế toán theo dõi danh sách hoa hồng phát sinh để chuẩn bị thanh toán.

Nên ưu tiên lọc các dòng:

- **Có thể thanh toán**: đã đủ điều kiện chi trả
- **Chờ xử lý**: chưa đến điều kiện chi trả
- **Đã thanh toán** hoặc **Đã chốt & Đã chi**: đã xử lý thanh toán

Trước khi thanh toán cần kiểm tra:

- Đúng người nhận
- Đúng số tiền
- Đúng điều kiện chi trả
- Đúng thông tin ngân hàng hoặc thông tin đối soát nội bộ

## 3. Cập nhật sau khi thanh toán

Sau khi thanh toán thành công ngoài hệ thống:

1. Cập nhật trạng thái phù hợp trên bản ghi hoa hồng.
2. Đính kèm chứng từ nếu quy trình nội bộ yêu cầu.
3. Kiểm tra lại để CTV nhận được thông báo tương ứng nếu họ đã bật Telegram.

Trong màn hình **Hoa hồng & Đối soát**, kế toán thường dùng các thao tác sau:

- **Đánh dấu có thể thanh toán**: đưa khoản hoa hồng sang diện có thể chi
- **Xác nhận chi hoa hồng**: xác nhận đã chi và lưu minh chứng chi nếu có
- **Hoàn tác Chốt sổ**: đưa khoản đã chốt quay lại trạng thái có thể thanh toán
- **Hoàn trả tiền**: dùng khi cần hoàn trạng thái thanh toán của học viên và hủy các khoản hoa hồng liên quan chưa được chi

## 4. Điều chỉnh hoa hồng

Trong các trường hợp như hoàn phí, đổi chương trình hoặc xử lý nghiệp vụ đặc biệt, kế toán có thể cần tạo điều chỉnh hoa hồng.

Nguyên tắc chung:

- Điều chỉnh âm khi cần thu hồi
- Ghi rõ lý do điều chỉnh
- Chọn đúng trạng thái nghiệp vụ theo quy trình nội bộ

## 5. Trường hợp học viên chuyển hệ đào tạo

Khi cán bộ hồ sơ hoặc quản trị viên thực hiện **Chuyển hệ đào tạo**, hệ thống sẽ tự tính lại chênh lệch lệ phí và chênh lệch hoa hồng.

Kế toán cần hiểu các nguyên tắc sau:

- Nếu hệ mới có mức chi trả khác hệ cũ, hệ thống sẽ tính lại số hoa hồng phù hợp
- Nếu khoản hoa hồng cũ **chưa chi**, các dòng chưa chi có thể bị hủy và thay bằng mức mới
- Nếu khoản hoa hồng cũ **đã chi rồi**, hệ thống có thể tạo dòng điều chỉnh tăng hoặc giảm để bù chênh lệch
- Nếu học viên đóng thừa do chuyển sang hệ có mức phí thấp hơn, hệ thống ghi nhận phần tiền thừa để chờ hoàn lại cho học viên

Kế toán cần kiểm tra:

1. Học viên đang chuyển từ hệ nào sang hệ nào.
2. Số tiền thừa hoặc thiếu sau chuyển hệ.
3. Các dòng hoa hồng mới phát sinh hoặc các dòng điều chỉnh tăng/giảm.
4. Trạng thái chi trả của hoa hồng cũ để quyết định có cần thu hồi hay không.

## 6. Trường hợp học viên rút hồ sơ hoặc hoàn trả tiền

Khi học viên rút hồ sơ, thao tác **Hoàn trả** sẽ đưa thanh toán về trạng thái **Đã hoàn trả** và học viên quay lại trạng thái ban đầu để có thể xử lý lại nếu cần.

Nguyên tắc xử lý hoa hồng:

- Nếu hoa hồng **chưa được chi**, hệ thống sẽ hủy các khoản hoa hồng liên quan
- Nếu hoa hồng **đã được xác nhận chi hoặc đã chi**, không thể hoàn trả trực tiếp từ màn hình đó; cần xử lý phần hoa hồng trước rồi mới hoàn trả

Kế toán cần thực hiện:

1. Kiểm tra xem các khoản hoa hồng liên quan đã ở trạng thái nào.
2. Nếu đã chốt chi hoặc đã chi, xử lý hoàn tác hoặc điều chỉnh trước.
3. Sau đó mới thực hiện hoàn trả tiền cho học viên.
4. Ghi rõ lý do để tiện đối soát về sau.

## 7. Trường hợp hoàn tiền thừa cho học viên

Sau khi chuyển hệ hoặc điều chỉnh lệ phí, nếu học viên đang có số tiền thừa, hệ thống sẽ đưa hồ sơ vào diện chờ hoàn tiền.

Kế toán xử lý như sau:

1. Lọc danh sách học viên ở trạng thái **Chờ hoàn tiền thừa**.
2. Mở thao tác **Xác nhận hoàn tiền thừa**.
3. Tải lên bằng chứng chuyển khoản hoàn tiền.
4. Ghi chú nội dung hoàn tiền nếu cần.

Sau khi xác nhận, hệ thống đánh dấu phần tiền thừa đã được xử lý.

## 8. Ghi chú về nút Xuất file Excel

Hiện tại trên màn hình **Hoa hồng & Đối soát** có nút mang nhãn **Xuất file Excel**.

Tuy nhiên, theo luồng đang có trong hệ thống, nút này hiện chưa chạy đúng một chức năng xuất file đối soát hoàn chỉnh. Khi sử dụng thực tế, cần kiểm tra lại với quản trị kỹ thuật trước khi dùng để chốt sổ.

Nếu cần bảng đối soát ngay lúc này, nên:

1. Lọc danh sách theo khoảng thời gian, trạng thái và người nhận.
2. Đối chiếu trực tiếp trên màn hình.
3. Thống nhất lại với quản trị kỹ thuật trước khi dùng file xuất cho quy trình chính thức.

## 9. Lưu ý nghiệp vụ

- Không tính hoa hồng thủ công nếu hệ thống đã có chính sách tự động.
- Luôn kiểm tra kỹ điều kiện mở chi trả của từng chính sách.
- Các thao tác xác minh và điều chỉnh đều nên được lưu lại đầy đủ để tiện đối soát sau này.
- Với các ca chuyển hệ hoặc rút hồ sơ, cần kiểm tra cả tiền học viên và hoa hồng trước khi thao tác.
