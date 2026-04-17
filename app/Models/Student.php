<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema as SchemaFacade;
use App\Models\StudentUpdateLog;

class Student extends Model {
    use HasFactory;
    use SoftDeletes;
    protected $fillable = [
        'profile_code',
        // I. Thông tin cơ bản
        'full_name',                // 4. Họ và tên
        'dob',                      // 5. Ngày sinh
        'birth_place',              // 6. Nơi sinh
        'permanent_residence',      // 7. Hộ khẩu thường trú
        'phone',                    // 8. Số điện thoại
        'ethnicity',                // 9. Dân tộc
        'gender',                   // 10. Giới tính
        'email',
        'address',
        'instructor',              // 3. GVHD

        // II. Thông tin CCCD
        'identity_card',            // 11. Số CCCD
        'identity_card_issue_date', // 12. Ngày cấp CCCD
        'identity_card_issue_place', // 13. Nơi cấp CCCD

        // III. Hồ sơ học tập – Cao đẳng
        'college_graduation_school',    // 17. Trường tốt nghiệp CĐ
        'college_graduation_major',     // 18. Ngành tốt nghiệp CĐ
        'college_graduation_grade',     // 19. Xếp loại tốt nghiệp CĐ
        'college_training_type',        // 20. Hệ tốt nghiệp CĐ
        'college_graduation_year',      // 21. Năm tốt nghiệp CĐ
        'college_diploma_number',       // 22. Số hiệu bằng CĐ
        'college_diploma_book_number',  // 23. Số vào sổ cấp bằng CĐ
        'college_diploma_issue_date',   // 24. Ngày ký bằng CĐ
        'college_diploma_signer',       // 25. Người ký bằng CĐ

        // IV. Hồ sơ học tập – Trung cấp & THPT
        'high_school_name',         // 28. Tên trường THPT
        'high_school_code',         // 29. Mã trường THPT
        'high_school_province',     // 37. Tên tỉnh/TP
        'high_school_province_code', // 38. Mã tỉnh
        'high_school_district',     // 39. Tên quận/huyện
        'high_school_district_code', // 40. Mã quận/huyện
        'priority_area',             // 41. Khu vực ưu tiên
        'high_school_graduation_year',      // 42. Năm tốt nghiệp THPT
        'high_school_academic_performance', // 43. Học lực cả năm
        'high_school_conduct',              // 44. Hạnh kiểm

        // V. Giấy tờ cá nhân (lưu đường dẫn / ghi chú)
        'document_birth_certificate',       // 30. Giấy khai sinh (BS/BG)
        'birth_certificate_copy_type',      // Loại bản sao / bản gốc
        'document_photo',                   // 31. Ảnh thẻ
        'document_health_certificate',      // 32. Giấy khám sức khỏe (BS/BG)
        'health_certificate_copy_type',     // Loại bản sao / bản gốc

        // VI. Thông tin đăng ký Liên thông
        'major',                    // 33. Ngành đăng ký liên thông
        'target_university',        // 34. Trường đăng ký liên thông
        'program_type',             // 35. Hệ đào tạo liên thông
        'intake_month',             // 36. Đợt đăng ký liên thông

        // VII. Thông tin khu vực – ưu tiên
        // (Khu vực ưu tiên riêng chưa lưu trong model)

        // VIII. Tuyển sinh & trạng thái hồ sơ
        'status',                   // 45. Trạng thái hồ sơ
        'application_status',       // 46. Tình trạng hồ sơ chi tiết
        'document_checklist',       // 47. Phiếu / checklist hồ sơ
        'source',                   // 48. Hình thức tuyển sinh
        'fee',                      // 49. Lệ phí
        'notes',                    // 50. Ghi chú

        'collaborator_id',
        'quota_id',
        'intake_id',

        // Các tài liệu chi tiết khác (mapping mở rộng từ file chuẩn)
        'document_college_diploma',         // 15. Bằng TN CĐ (BS/BG)
        'college_diploma_copy_type',        // Loại bản sao / bản gốc
        'document_college_transcript',      // 16. Bảng điểm CĐ (BS/BG)
        'college_transcript_copy_type',     // Loại bản sao / bản gốc
        'document_high_school_diploma',     // 26. Bằng TN THPT (BS/BG)
        'high_school_diploma_copy_type',    // Loại bản sao / bản gốc
        'document_identity_card_front', // 14. File CCCD - mặt trước
        'document_identity_card_back',  // 14. File CCCD - mặt sau
        'document_intermediate_diploma',    // 27. Bằng Trung cấp
        'document_intermediate_transcript', // Bảng điểm Trung cấp
    ];

    protected $casts = [
        'document_checklist' => 'array',
    ];

    public function getProgramTypeLabelAttribute(): string {
        return match (strtoupper((string) $this->program_type)) {
            'REGULAR' => 'Chính quy',
            'PART_TIME' => 'Vừa học vừa làm',
            'DISTANCE' => 'Đào tạo từ xa',
            default => $this->program_type ?: 'Chưa xác định',
        };
    }

    // Enum StudentStatus - Pipeline quản lý hành trình nhập học
    public const STATUS_NEW = 'new';                    // Mới
    public const STATUS_CONTACTED = 'contacted';        // Đã liên hệ
    public const STATUS_SUBMITTED = 'submitted';        // Đã nộp hồ sơ
    public const STATUS_APPROVED = 'approved';        // Đã duyệt
    public const STATUS_ENROLLED = 'enrolled';          // Đã nhập học
    public const STATUS_REJECTED = 'rejected';          // Từ chối
    public const STATUS_DROPPED = 'dropped';            // Bỏ học

    public static function getStatusOptions(): array {
        return [
            self::STATUS_NEW => 'Mới',
            self::STATUS_CONTACTED => 'Đã liên hệ',
            self::STATUS_SUBMITTED => 'Chờ xác minh',
            self::STATUS_APPROVED => 'Đã duyệt',
            self::STATUS_ENROLLED => 'Đã nhập học',
            self::STATUS_REJECTED => 'Từ chối',
            self::STATUS_DROPPED => 'Bỏ học',
        ];
    }

    /**
     * Kiểm tra xem sinh viên có thể chuyển sang trạng thái tiếp theo không
     */
    public function canAdvanceToNextStatus(): bool {
        $nextStatuses = [
            self::STATUS_NEW => [self::STATUS_CONTACTED, self::STATUS_DROPPED],
            self::STATUS_CONTACTED => [self::STATUS_SUBMITTED, self::STATUS_DROPPED],
            self::STATUS_SUBMITTED => [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_DROPPED],
            self::STATUS_APPROVED => [self::STATUS_ENROLLED, self::STATUS_REJECTED, self::STATUS_DROPPED],
            self::STATUS_ENROLLED => [self::STATUS_DROPPED],
            self::STATUS_REJECTED => [],
            self::STATUS_DROPPED => [],
        ];

        return !empty($nextStatuses[$this->status] ?? []);
    }

    /**
     * Lấy danh sách trạng thái tiếp theo có thể chuyển đến
     */
    public function getNextAvailableStatuses(): array {
        $nextStatuses = [
            self::STATUS_NEW => [self::STATUS_CONTACTED, self::STATUS_DROPPED],
            self::STATUS_CONTACTED => [self::STATUS_SUBMITTED, self::STATUS_DROPPED],
            self::STATUS_SUBMITTED => [self::STATUS_APPROVED, self::STATUS_REJECTED, self::STATUS_DROPPED],
            self::STATUS_APPROVED => [self::STATUS_ENROLLED, self::STATUS_REJECTED, self::STATUS_DROPPED],
            self::STATUS_ENROLLED => [self::STATUS_DROPPED],
            self::STATUS_REJECTED => [],
            self::STATUS_DROPPED => [],
        ];

        return $nextStatuses[$this->status] ?? [];
    }

    /**
     * Kiểm tra xem sinh viên đã hoàn thành quy trình chưa
     */
    public function isCompleted(): bool {
        return in_array($this->status, [self::STATUS_ENROLLED, self::STATUS_REJECTED, self::STATUS_DROPPED]);
    }


    public function collaborator() {
        return $this->belongsTo(Collaborator::class, 'collaborator_id');
    }

    public function quota() {
        return $this->belongsTo(Quota::class, 'quota_id');
    }

    public function intake() {
        return $this->belongsTo(Intake::class, 'intake_id');
    }

    public function payment() {
        return $this->hasOne(Payment::class);
    }

    public function updateLogs() {
        return $this->hasMany(StudentUpdateLog::class)->latest();
    }

    protected static function booted(): void {
        static::created(function (Student $student) {
            if (!empty($student->profile_code)) {
                return;
            }

            $year = $student->created_at?->format('Y') ?? now()->format('Y');
            $student->profile_code = sprintf('HS%s%06d', $year, $student->id);
            $student->saveQuietly();
        });

        static::saving(function (Student $student) {
            // Nếu học viên "đến trực tiếp" (văn phòng tuyển sinh) thì không được gán CTV giới thiệu
            // (kể cả khi client/UI gửi lên collaborator_id).
            if (($student->source ?? null) === 'walkin') {
                $student->collaborator_id = null;
            }

            if (array_key_exists('intake_id', $student->getDirty())) {
                if ($student->intake_id) {
                    $intake = Intake::find($student->intake_id);
                    $student->intake_month = $intake?->start_date?->format('n');
                } else {
                    $student->intake_month = null;
                }
            }

            if (array_key_exists('quota_id', $student->getDirty()) && $student->quota_id) {
                $quota = \App\Models\Quota::find($student->quota_id);
                if ($quota) {
                    $student->major = $quota->major_name ?? $quota->name;
                    $student->program_type = $quota->program_name;
                }
            }
        });

        static::updated(function (Student $student) {
            // Nếu chưa có bảng log (ví dụ môi trường dev/test chưa migrate) thì bỏ qua
            if (!SchemaFacade::hasTable('student_update_logs')) {
                return;
            }

            $changes = [];
            $dirty = $student->getChanges();

            foreach ($dirty as $field => $newValue) {
                if ($field === 'updated_at') {
                    continue;
                }
                $oldValue = $student->getOriginal($field);
                if ($oldValue === $newValue) {
                    continue;
                }

                $changes[] = [
                    'field' => $field,
                    'from' => $oldValue,
                    'to' => $newValue,
                ];
            }

            if (empty($changes)) {
                return;
            }

            StudentUpdateLog::create([
                'student_id' => $student->id,
                'user_id' => Auth::id(),
                'changes' => $changes,
            ]);
        });
    }

    /**
     * Validation rules cho model
     */
    public static function getValidationRules(): array {
        return [
            'full_name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:students,phone,' . (request()->route('record') ?? ''),
            'email' => 'nullable|email|max:255|unique:students,email,' . (request()->route('record') ?? ''),
            'identity_card' => 'nullable|string|max:20|unique:students,identity_card,' . (request()->route('record') ?? ''),
            'collaborator_id' => 'nullable|exists:collaborators,id',
            'quota_id' => 'nullable|exists:quotas,id',
            'target_university' => 'nullable|string|max:255',
            'major' => 'nullable|string|max:255',
            'intake_id' => 'nullable|exists:intakes,id',
            'intake_month' => 'nullable|integer|between:1,12',
            'program_type' => ['nullable', Rule::in(['REGULAR', 'PART_TIME'])],
            'source' => ['required', Rule::in(['form', 'ref', 'facebook', 'zalo', 'tiktok', 'hotline', 'event', 'school', 'walkin', 'other'])],
            'status' => ['required', Rule::in([
                self::STATUS_NEW,
                self::STATUS_CONTACTED,
                self::STATUS_SUBMITTED,
                self::STATUS_APPROVED,
                self::STATUS_ENROLLED,
                self::STATUS_REJECTED,
                self::STATUS_DROPPED
            ])],
            'notes' => 'nullable|string',
            'dob' => 'nullable|date|before:today',
            'address' => 'nullable|string|max:500',
        ];
    }
}
