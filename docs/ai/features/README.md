---
phase: planning
title: Danh sách features theo từng vai trò
description: Tổng hợp các feature-* chính của hệ thống CRM Tuyển sinh Liên thông, nhóm theo vai trò nghiệp vụ
---

## Sinh viên

-   `feature-student-register-profile`: Đăng ký / khởi tạo hồ sơ sinh viên.
-   `feature-student-edit-profile`: Xem & cập nhật thông tin cá nhân, THPT, CĐ/TC.
-   `feature-student-upload-documents`: Upload & quản lý giấy tờ minh chứng.
-   `feature-student-submit-application`: Nộp hồ sơ tuyển sinh (Draft → Submitted).
-   `feature-student-track-application-status`: Theo dõi trạng thái hồ sơ & checklist giấy tờ.
-   `feature-student-payment-status`: Xem trạng thái và thông tin thanh toán.
-   `feature-student-change-intake-or-major`: Gửi yêu cầu đổi đợt / đổi nguyện vọng.
-   `feature-student-data-lock-rules`: Rule khoá – duyệt – chỉnh sửa dữ liệu phía sinh viên.

## Cộng tác viên

-   `feature-ctv-create-student-lead`: CTV tạo lead / hồ sơ sinh viên.
-   `feature-ctv-student-pipeline`: Xem danh sách & pipeline sinh viên trong nhánh.
-   `feature-ctv-track-student-status`: Theo dõi trạng thái hồ sơ & thanh toán sinh viên.
-   `feature-ctv-support-student-payment`: Hỗ trợ sinh viên nộp lệ phí & xác nhận ban đầu.
-   `feature-ctv-commission-overview`: Xem tổng quan hoa hồng CTV.
-   `feature-ctv-wallet-transactions`: Ví hoa hồng & lịch sử giao dịch.
-   `feature-ctv-downline-management`: Quản lý downline & doanh thu từ nhánh.
-   `feature-ctv-access-control`: Quyền truy cập dữ liệu theo nhánh CTV.

## Cán bộ hồ sơ (Cô Ly)

-   `feature-document-officer-student-list`: Danh sách & lọc hồ sơ sinh viên cần xử lý.
-   `feature-document-officer-view-application-detail`: Xem chi tiết hồ sơ & checklist giấy tờ.
-   `feature-document-officer-major-eligibility`: Đánh giá điều kiện ngành & quyết định hồ sơ.
-   `feature-document-officer-exception-edit`: Chỉnh sửa ngoại lệ & lưu lịch sử thay đổi.
-   `feature-document-officer-request-more-documents`: Yêu cầu bổ sung giấy tờ & ghi chú.
-   `feature-document-officer-verify-payment`: Phối hợp xác nhận thanh toán (verify payment).
-   `feature-document-officer-export-to-school`: Chuẩn bị & export danh sách gửi Trường.
-   `feature-document-officer-audit-log`: Audit log cho mọi thao tác trên hồ sơ.

## Kế toán

-   `feature-accountant-payment-list`: Danh sách & lọc các thanh toán tuyển sinh.
-   `feature-accountant-verify-payment`: Đối soát & xác nhận thanh toán.
-   `feature-accountant-upload-receipt`: Upload & quản lý phiếu thu / chứng từ.
-   `feature-accountant-commission-payout`: Đối soát & chi trả hoa hồng CTV.
-   `feature-accountant-wallet-adjustments`: Giao dịch ví hoa hồng & cập nhật số dư.
-   `feature-accountant-financial-reports`: Báo cáo doanh thu & hoa hồng (export Excel).
-   `feature-accountant-audit-log`: Audit log cho các thao tác tài chính.

## Quản lý tổ chức (Cô Vinh)

-   `feature-org-owner-manage-organization`: Xem & cập nhật thông tin tổ chức.
-   `feature-org-owner-manage-members`: Quản lý user nội bộ & CTV trong tổ chức.
-   `feature-org-owner-program-and-major-config`: Cấu hình ngành & chương trình.
-   `feature-org-owner-intake-and-quota-config`: Cấu hình đợt tuyển sinh & quota.
-   `feature-org-owner-commission-policy-config`: Cấu hình chính sách hoa hồng.
-   `feature-org-owner-dashboard`: Dashboard & báo cáo tổng quan cho tổ chức.
-   `feature-org-owner-operational-rules`: Thiết lập rule vận hành (khoá field, cảnh báo quota, v.v.).
-   `feature-org-owner-config-audit-log`: Audit log cho mọi thay đổi cấu hình tổ chức.
