# Bảng Mapping: Danh sách chuẩn (50 trường) vs Code hiện tại

## I. Thông tin cơ bản

| #   | Trường chuẩn       | Field hiện tại        | Trạng thái                 | Ghi chú                                    |
| --- | ------------------ | --------------------- | -------------------------- | ------------------------------------------ |
| 1   | STT                | -                     | ❌ Thiếu                   | Có thể tự động, không cần field            |
| 2   | Ngày tháng         | `created_at`          | ⚠️ Có nhưng không hiển thị | Cần thêm vào form                          |
| 3   | GVHD               | -                     | ❌ Thiếu                   | Cần thêm field `instructor` hoặc `advisor` |
| 4   | Họ và tên          | `full_name`           | ✅ Có                      | OK                                         |
| 5   | Ngày sinh          | `dob`                 | ✅ Có                      | OK                                         |
| 6   | Nơi sinh           | `birth_place`         | ✅ Có                      | OK                                         |
| 7   | Hộ khẩu thường trú | `permanent_residence` | ✅ Có                      | OK                                         |
| 8   | Số điện thoại      | `phone`               | ✅ Có                      | OK                                         |
| 9   | Dân tộc            | `ethnicity`           | ✅ Có                      | OK                                         |
| 10  | Giới tính          | `gender`              | ✅ Có                      | OK                                         |

## II. Thông tin CCCD

| #   | Trường chuẩn  | Field hiện tại                                                | Trạng thái                                   | Ghi chú                      |
| --- | ------------- | ------------------------------------------------------------- | -------------------------------------------- | ---------------------------- |
| 11  | Số CCCD       | `identity_card`                                               | ✅ Có                                        | OK                           |
| 12  | Ngày cấp CCCD | `identity_card_issue_date`                                    | ✅ Có                                        | OK                           |
| 13  | Nơi cấp CCCD  | `identity_card_issue_place`                                   | ✅ Có                                        | OK                           |
| 14  | File CCCD     | `document_identity_card_front`, `document_identity_card_back` | ⚠️ Có nhưng không có field riêng "File CCCD" | Có thể dùng 2 field hiện tại |

## III. Hồ sơ học tập – Cao đẳng

| #   | Trường chuẩn                 | Field hiện tại                | Trạng thái                        | Ghi chú                  |
| --- | ---------------------------- | ----------------------------- | --------------------------------- | ------------------------ |
| 15  | Bằng tốt nghiệp CĐ (BS / BG) | `document_college_diploma`    | ⚠️ Có nhưng không phân biệt BS/BG | Cần thêm field phân biệt |
| 16  | Bảng điểm CĐ (BS / BG)       | `document_college_transcript` | ⚠️ Có nhưng không phân biệt BS/BG | Cần thêm field phân biệt |
| 17  | Trường tốt nghiệp CĐ         | `college_graduation_school`   | ✅ Có                             | OK                       |
| 18  | Ngành tốt nghiệp CĐ          | `college_graduation_major`    | ✅ Có                             | OK                       |
| 19  | Xếp loại tốt nghiệp CĐ       | `college_graduation_grade`    | ✅ Có                             | OK                       |
| 20  | Hệ tốt nghiệp CĐ             | `college_training_type`       | ✅ Có                             | OK                       |
| 21  | Năm tốt nghiệp CĐ            | `college_graduation_year`     | ✅ Có                             | OK                       |
| 22  | Số hiệu bằng CĐ              | `college_diploma_number`      | ✅ Có                             | OK                       |
| 23  | Số vào sổ cấp bằng CĐ        | `college_diploma_book_number` | ✅ Có                             | OK                       |
| 24  | Ngày ký bằng CĐ              | `college_diploma_issue_date`  | ✅ Có                             | OK                       |
| 25  | Người ký bằng CĐ             | `college_diploma_signer`      | ✅ Có                             | OK                       |

## IV. Hồ sơ học tập – Trung cấp & THPT

| #   | Trường chuẩn                   | Field hiện tại                 | Trạng thái                        | Ghi chú                                        |
| --- | ------------------------------ | ------------------------------ | --------------------------------- | ---------------------------------------------- |
| 26  | Bằng tốt nghiệp THPT (BS / BG) | `document_high_school_diploma` | ⚠️ Có nhưng không phân biệt BS/BG | Cần thêm field phân biệt                       |
| 27  | Bằng Trung cấp                 | -                              | ❌ Thiếu                          | Cần thêm field `document_intermediate_diploma` |
| 28  | Tên trường THPT                | `high_school_name`             | ✅ Có                             | OK                                             |
| 29  | Mã trường THPT                 | `high_school_code`             | ✅ Có                             | OK                                             |

**Lưu ý:** Có các field `intermediate_*` nhưng không có field riêng cho "Bằng Trung cấp" (document). Cần thêm field `document_intermediate_diploma` và `document_intermediate_transcript`.

## V. Giấy tờ cá nhân

| #   | Trường chuẩn                 | Field hiện tại                | Trạng thái                         | Ghi chú                  |
| --- | ---------------------------- | ----------------------------- | ---------------------------------- | ------------------------ |
| 30  | Giấy khai sinh (BS / BG)     | `document_birth_certificate`  | ⚠️ Có nhưng không phân biệt BS/BG  | Cần thêm field phân biệt |
| 31  | Ảnh thẻ                      | `document_photo`              | ⚠️ Có nhưng label là "Ảnh cá nhân" | Cần đổi label            |
| 32  | Giấy khám sức khỏe (BS / BG) | `document_health_certificate` | ⚠️ Có nhưng không phân biệt BS/BG  | Cần thêm field phân biệt |

## VI. Thông tin đăng ký Liên thông

| #   | Trường chuẩn              | Field hiện tại      | Trạng thái                      | Ghi chú                                     |
| --- | ------------------------- | ------------------- | ------------------------------- | ------------------------------------------- |
| 33  | Ngành đăng ký liên thông  | `major`             | ⚠️ Label là "Ngành đăng ký học" | Cần đổi label                               |
| 34  | Trường đăng ký liên thông | `target_university` | ⚠️ Có nhưng không rõ ràng       | Cần kiểm tra và đổi label                   |
| 35  | Hệ đào tạo liên thông     | `program_type`      | ⚠️ Label là "Hệ liên thông"     | Cần đổi label thành "Hệ đào tạo liên thông" |
| 36  | Đợt đăng ký liên thông    | `intake_month`      | ⚠️ Label là "Đợt tuyển"         | Cần đổi label                               |

## VII. Thông tin khu vực – ưu tiên

| #   | Trường chuẩn         | Field hiện tại              | Trạng thái | Ghi chú                        |
| --- | -------------------- | --------------------------- | ---------- | ------------------------------ |
| 37  | Tên tỉnh / thành phố | `high_school_province`      | ✅ Có      | OK (trong tab THPT)            |
| 38  | Mã tỉnh              | `high_school_province_code` | ✅ Có      | OK (trong tab THPT)            |
| 39  | Tên quận / huyện     | `high_school_district`      | ✅ Có      | OK (trong tab THPT)            |
| 40  | Mã quận / huyện      | `high_school_district_code` | ✅ Có      | OK (trong tab THPT)            |
| 41  | Khu vực ưu tiên      | -                           | ❌ Thiếu   | Cần thêm field `priority_area` |

## VIII. Kết quả học tập THPT

| #   | Trường chuẩn        | Field hiện tại                     | Trạng thái | Ghi chú |
| --- | ------------------- | ---------------------------------- | ---------- | ------- |
| 42  | Năm tốt nghiệp THPT | `high_school_graduation_year`      | ✅ Có      | OK      |
| 43  | Học lực cả năm      | `high_school_academic_performance` | ✅ Có      | OK      |
| 44  | Hạnh kiểm           | `high_school_conduct`              | ✅ Có      | OK      |

## IX. Tuyển sinh & trạng thái hồ sơ

| #   | Trường chuẩn         | Field hiện tại | Trạng thái          | Ghi chú                                                           |
| --- | -------------------- | -------------- | ------------------- | ----------------------------------------------------------------- |
| 45  | Trạng thái hồ sơ     | `status`       | ✅ Có               | OK (có trong resource, không trong form)                          |
| 46  | Tình trạng hồ sơ     | -              | ❌ Thiếu            | Cần thêm field `application_condition` hoặc dùng `status`         |
| 47  | Phiếu tuyển sinh     | -              | ❌ Thiếu            | Có thể dùng trong `document_checklist` nhưng không có field riêng |
| 48  | Hình thức tuyển sinh | `source`       | ⚠️ Label là "Nguồn" | Cần đổi label hoặc thêm field riêng                               |
| 49  | Lệ phí               | -              | ❌ Thiếu            | Cần thêm field `fee` hoặc liên kết với Payment model              |
| 50  | Ghi chú              | `notes`        | ✅ Có               | OK                                                                |

## Tổng kết

-   ✅ **Có đầy đủ:** 30 trường
-   ⚠️ **Có nhưng cần điều chỉnh:** 12 trường (label, phân biệt BS/BG, v.v.)
-   ❌ **Thiếu:** 8 trường cần thêm

## Các trường cần thêm vào database và form:

1. `instructor` hoặc `advisor` (GVHD)
2. `registration_date` (Ngày tháng) - có thể dùng `created_at`
3. `document_intermediate_diploma` (Bằng Trung cấp)
4. `document_intermediate_transcript` (Bảng điểm Trung cấp)
5. `priority_area` (Khu vực ưu tiên)
6. `application_condition` (Tình trạng hồ sơ) - hoặc dùng `status`
7. `admission_form` (Phiếu tuyển sinh) - hoặc dùng trong checklist
8. `fee` (Lệ phí) - hoặc liên kết với Payment model

## Các trường cần phân biệt BS/BG:

-   Bằng tốt nghiệp CĐ (BS/BG)
-   Bảng điểm CĐ (BS/BG)
-   Bằng tốt nghiệp THPT (BS/BG)
-   Giấy khai sinh (BS/BG)
-   Giấy khám sức khỏe (BS/BG)

**Giải pháp:** Có thể thêm các field như `document_college_diploma_type` (enum: 'BS', 'BG') hoặc tách thành 2 field riêng.
