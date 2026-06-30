# CRM Tuyển sinh Liên thông

Hệ thống CRM hỗ trợ quản lý tuyển sinh liên thông theo hướng `API-first`, tập trung vào các nghiệp vụ:

- Tiếp nhận học viên từ link giới thiệu
- Quản lý hồ sơ và trạng thái xét duyệt
- Ghi nhận và xác minh thanh toán
- Quản lý chỉ tiêu tuyển sinh
- Tính và đối soát hoa hồng cộng tác viên
- Gửi thông báo qua các kênh nội bộ và Telegram

## Tài liệu chính

- Tổng quan yêu cầu: `docs/README.md`
- Hướng dẫn sử dụng theo vai trò: `docs/guide/README.md`
- Tài liệu nghiệp vụ: `docs/business/`
- Tài liệu AI DevKit: `docs/ai/`

## Các luồng công khai đang dùng

- Đăng ký học viên: `/ref/{ref_id}`
- Nộp minh chứng chuyển khoản: `/ref/{ref_id}/payment`
- Tra cứu hồ sơ: `/hoso` hoặc `/hoso/{profile_code}`

## Ghi chú

- Bộ tài liệu hướng dẫn trong `docs/guide/` đã được chỉnh theo hệ thống hiện tại.
- Nếu thay đổi màn hình quản trị, trạng thái nghiệp vụ hoặc cấu hình Telegram, cần cập nhật tài liệu tương ứng.
