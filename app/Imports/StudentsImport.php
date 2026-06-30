<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\Collaborator;
use App\Models\Intake;
use App\Models\Quota;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Str;

class StudentsImport implements ToCollection, WithHeadingRow {
    public array $successRows = [];
    public array $skippedRows = [];
    public array $validationErrors = [];

    public function collection(Collection $rows) {
        // Tạm thời tắt thông báo tự động (email, telegram) nhưng VẪN giữ logic của Observer (sinh mã hồ sơ, tính quota...)
        \App\Observers\StudentObserver::$skipNotifications = true;

        try {
            foreach ($rows as $index => $row) {
                $rowNum = $index + 2; // Dòng excel thực tế (heading row là dòng 1)
                
                // Chuẩn hóa dữ liệu thô từ hàng
                $fullName = trim($row['ho_va_ten'] ?? $row['ho_ten'] ?? $row['hoc_va_ten'] ?? '');
                $phone = trim($row['so_dien_thoai'] ?? $row['sdt'] ?? $row['phone'] ?? '');
                $email = trim($row['email'] ?? '');
                $programTypeRaw = trim($row['he_dao_tao'] ?? $row['he_tuyen_sinh'] ?? $row['he_dklt'] ?? '');
                $major = trim($row['nganh_hoc'] ?? $row['nganh_dang_ky'] ?? $row['nganh_dklt'] ?? '');
                $collabRaw = trim($row['nguoi_gioi_thieu'] ?? $row['ctv'] ?? $row['gvhd'] ?? '');
                $targetUniversity = trim($row['truong_dang_ky'] ?? $row['target_university'] ?? $row['truong_dklt'] ?? '');
                $dobRaw = trim($row['ngay_sinh'] ?? $row['dob'] ?? '');
                $birthPlace = trim($row['noi_sinh'] ?? $row['birth_place'] ?? '');
                $identityCard = trim($row['cccd'] ?? $row['identity_card'] ?? '');

                // Nếu cả Họ tên và SĐT đều trống (ví dụ: hàng sub-header hoặc hàng trống cuối file), bỏ qua không báo lỗi
                if (empty($fullName) && empty($phone)) {
                    continue;
                }

                // Bỏ qua dòng nếu trùng lặp tiêu đề chính
                if ($fullName === 'Họ và tên' || $phone === 'Số điện thoại') {
                    continue;
                }

                // 1. Validation cơ bản các trường bắt buộc
                if (empty($fullName)) {
                    $this->validationErrors[] = "Dòng {$rowNum}: Thiếu Họ và tên học viên.";
                    continue;
                }
                if (empty($phone)) {
                    $this->validationErrors[] = "Dòng {$rowNum}: Thiếu Số điện thoại học viên.";
                    continue;
                }
                if (empty($programTypeRaw)) {
                    $this->validationErrors[] = "Dòng {$rowNum}: Thiếu Hệ đào tạo học viên.";
                    continue;
                }
                if (empty($major)) {
                    $this->validationErrors[] = "Dòng {$rowNum}: Thiếu Ngành học học viên.";
                    continue;
                }

                // 2. Chuẩn hóa Hệ đào tạo
                $programType = null;
                $programTypeLower = mb_strtolower($programTypeRaw, 'UTF-8');
                if (str_contains($programTypeLower, 'chính quy') || $programTypeLower === 'regular') {
                    $programType = Student::PROGRAM_REGULAR;
                } elseif (str_contains($programTypeLower, 'vừa học vừa làm') || str_contains($programTypeLower, 'vhvl') || $programTypeLower === 'part_time') {
                    $programType = Student::PROGRAM_PART_TIME;
                } elseif (str_contains($programTypeLower, 'từ xa') || str_contains($programTypeLower, 'tuxa') || $programTypeLower === 'distance') {
                    $programType = Student::PROGRAM_DISTANCE;
                }

                if (!$programType) {
                    $this->validationErrors[] = "Dòng {$rowNum}: Hệ đào tạo không hợp lệ ('{$programTypeRaw}'). Phải là: Chính quy, Vừa học vừa làm hoặc Từ xa.";
                    continue;
                }

                // Parse Ngày sinh
                $dob = null;
                if (!empty($dobRaw)) {
                    if (is_numeric($dobRaw)) {
                        try {
                            $dob = \Carbon\Carbon::instance(\PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($dobRaw))->format('Y-m-d');
                        } catch (\Exception $e) {
                            $dob = null;
                        }
                    } else {
                        try {
                            $dob = \Carbon\Carbon::createFromFormat('d/m/Y', $dobRaw)->format('Y-m-d');
                        } catch (\Exception $e) {
                            try {
                                $dob = \Carbon\Carbon::parse($dobRaw)->format('Y-m-d');
                            } catch (\Exception $e2) {
                                $dob = null;
                            }
                        }
                    }
                }

                // 3. Kiểm tra trùng lặp SĐT học viên
                $existingStudent = Student::where('phone', $phone)->first();
                if ($existingStudent) {
                    $this->skippedRows[] = [
                        'row' => $rowNum,
                        'name' => $fullName,
                        'phone' => $phone,
                        'reason' => "Trùng Số điện thoại với học viên đã có: {$existingStudent->full_name} (ID: {$existingStudent->id})"
                    ];
                    continue;
                }

                // 4. Đối chiếu Người giới thiệu (CTV)
                $collaboratorId = null;
                if (!empty($collabRaw)) {
                    // Thử tìm theo SĐT trước hoặc Tên
                    $collaborator = Collaborator::where('phone', $collabRaw)
                        ->orWhere('full_name', 'like', "%{$collabRaw}%")
                        ->first();
                    if ($collaborator) {
                        $collaboratorId = $collaborator->id;
                    } else {
                        // Nếu không tìm thấy, log warning hoặc đánh dấu CTV không tồn tại
                        $this->validationErrors[] = "Dòng {$rowNum}: Người giới thiệu '{$collabRaw}' chưa tồn tại trong hệ thống. Đã để trống trường này.";
                    }
                }

                // 5. Xác định Intake mặc định nếu có
                $intake = Intake::where('status', 'active')->first() ?: Intake::orderBy('id', 'desc')->first();

                // Tự động tìm Quota (Chương trình tuyển sinh) phù hợp
                $quotaId = null;
                if ($intake && !empty($major)) {
                    $quota = Quota::where('intake_id', $intake->id)
                        ->where(function($q) use ($major) {
                            $q->whereRaw('LOWER(major_name) LIKE ?', ['%' . mb_strtolower($major, 'UTF-8') . '%'])
                              ->orWhereRaw('? LIKE LOWER(major_name)', ['%' . mb_strtolower($major, 'UTF-8') . '%']);
                        })
                        ->whereRaw('LOWER(program_name) = ?', [strtolower($programType)])
                        ->first();
                    if ($quota) {
                        $quotaId = $quota->id;
                    }
                }

                // Normalize program_type based on DB driver
                $dbDriver = \Illuminate\Support\Facades\DB::getDriverName();
                $normalizedProgramType = ($dbDriver === 'sqlite') ? strtoupper($programType) : strtolower($programType);

                // 6. Tạo mới học viên
                $student = Student::create([
                    'full_name'          => $fullName,
                    'phone'              => $phone,
                    'email'              => !empty($email) ? $email : null,
                    'dob'                => $dob,
                    'birth_place'        => !empty($birthPlace) ? $birthPlace : null,
                    'identity_card'      => !empty($identityCard) ? $identityCard : null,
                    'program_type'       => $normalizedProgramType,
                    'major'              => $major,
                    'target_university'  => !empty($targetUniversity) ? $targetUniversity : null,
                    'collaborator_id'    => $collaboratorId,
                    'intake_id'          => $intake ? $intake->id : null,
                    'quota_id'           => $quotaId,
                    'status'             => 'new',
                    'application_status' => 'draft',
                    'source'             => $collaboratorId ? 'ref' : 'form',
                ]);

                $this->successRows[] = [
                    'row'   => $rowNum,
                    'name'  => $fullName,
                    'phone' => $phone,
                ];
            }
        } finally {
            // Khôi phục lại trạng thái bình thường sau khi import xong
            \App\Observers\StudentObserver::$skipNotifications = false;
        }
    }
}
