# 03. Workflows & Lifecycle (Quy trình nghiệp vụ)

Tài liệu này mô tả các luồng xử lý trạng thái và các bước vận hành của hệ thống.

## 1. Hành trình của Học viên (Student Lifecycle)

### Các trạng thái (Status):
*   **Mới (New):** Dữ liệu vừa đổ về từ Form hoặc CTV nhập nháp.
*   **Đã liên hệ (Contacted):** Tuyển sinh đã gọi điện tư vấn.
*   **Chờ xác minh (Submitted):** Học viên đã nộp tiền/bill, chờ văn phòng kiểm tra.
*   **Đã duyệt (Approved):** Văn phòng đã xác nhận hồ sơ và tiền hợp lệ.
*   **Đã nhập học (Enrolled):** Sinh viên đã có tên trong danh sách lớp chính thức (Điểm kích hoạt hoa hồng trả sau).
*   **Từ chối (Rejected):** Hồ sơ không đủ điều kiện.
*   **Bỏ học (Dropped):** Sinh viên nghỉ giữa chừng.

### Điều kiện chuyển trạng thái:
Hệ thống sử dụng một "Pipeline" chặt chẽ, không cho phép nhảy cóc các bước quan trọng (ví dụ: không thể lên `Approved` nếu chưa qua `Submitted`).

## 2. Quy trình Thanh toán & Minh chứng (Payment Workflow)

1.  **Bước 1 (CTV):** Upload ảnh Bill chuyển khoản và nhập số tiền. Trạng thái Payment chuyển sang `submitted`.
2.  **Bước 2 (Văn phòng):**
    *   Kiểm tra tiền về tài khoản ngân hàng.
    *   Nếu khớp: Upload ảnh **Phiếu thu** chính thức, nhập **Số phiếu thu**, xác nhận.
    *   Trạng thái Payment chuyển sang `verified`.
3.  **Bước 3 (Tự động):** Ngay khi `verified`, hệ thống gọi `CommissionService` để sinh hoa hồng.

## 3. Quản lý Hồ sơ giấy tờ (Document Checklist)
Mỗi sinh viên cần bộ hồ sơ gồm:
*   Phiếu tuyển sinh.
*   Bằng Cao đẳng / THPT / Trung cấp (Bản sao/Gốc).
*   Bảng điểm.
*   Giấy khai sinh.
*   CCCD (Mặt trước/sau).
*   Giấy khám sức khỏe.
*   Ảnh thẻ.

**Trạng thái hồ sơ (`application_status`)** sẽ tự động tính toán dựa trên checklist này:
*   Nếu thiếu bất kỳ giấy tờ nào -> `pending_documents`.
*   Nếu đủ giấy tờ + Đã nhập học -> `eligible`.

## 4. Quy trình Hoàn trả (Reversal)
Trong trường hợp xác nhận sai, quản trị viên có quyền "Hoàn trả trạng thái":
*   Yêu cầu nhập **Lý do hoàn trả**.
*   Chuyển trạng thái Payment từ `verified` về `reverted`.
*   Ghi nhật ký (Audit Log) chi tiết ai làm, lúc nào, lý do gì.

---
> **Lưu ý cho AI Agent:** Trong ERPNext, hãy sử dụng tính năng **Workflow** để quản lý các trạng thái này. Sử dụng **Custom Fields** kiểu `Check` hoặc `Table` để làm Document Checklist.
