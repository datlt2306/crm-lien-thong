<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Validation\Rule;

class Student extends Model {
    use HasFactory;
    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'identity_card',
        'organization_id',
        'collaborator_id',
        'major_id',
        'intake_id',
        'target_university',
        'major',
        'intake_month',
        'program_type',
        'source',
        'status',
        'notes',
        'dob',
        'address',
        'document_checklist',
        // Thông tin cá nhân
        'birth_place',
        'permanent_residence',
        'ethnicity',
        'gender',
        'identity_card_issue_date',
        'identity_card_issue_place',
        // Thông tin THPT
        'high_school_name',
        'high_school_code',
        'high_school_province',
        'high_school_province_code',
        'high_school_district',
        'high_school_district_code',
        'high_school_graduation_year',
        'high_school_academic_performance',
        'high_school_conduct',
        // Thông tin văn bằng CĐ
        'college_graduation_school',
        'college_graduation_major',
        'college_graduation_grade',
        'college_training_type',
        'college_graduation_year',
        'college_diploma_number',
        'college_diploma_book_number',
        'college_diploma_issue_date',
        'college_diploma_signer',
        // Thông tin văn bằng TC
        'intermediate_graduation_school',
        'intermediate_graduation_major',
        'intermediate_graduation_grade',
        'intermediate_training_type',
        'intermediate_graduation_year',
        'intermediate_diploma_number',
        'intermediate_diploma_book_number',
        'intermediate_diploma_issue_date',
        'intermediate_diploma_signer',
        // File uploads
        'document_college_diploma',
        'document_college_transcript',
        'document_high_school_diploma',
        'document_birth_certificate',
        'document_identity_card_front',
        'document_identity_card_back',
        'document_photo',
        'document_health_certificate',
    ];

    protected $casts = [
        'document_checklist' => 'array',
    ];

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

    public function organization() {
        return $this->belongsTo(Organization::class, 'organization_id');
    }

    public function collaborator() {
        return $this->belongsTo(Collaborator::class, 'collaborator_id');
    }

    public function major() {
        return $this->belongsTo(Major::class, 'major_id');
    }

    public function intake() {
        return $this->belongsTo(Intake::class, 'intake_id');
    }

    public function payment() {
        return $this->hasOne(Payment::class);
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
            'organization_id' => 'required|exists:organizations,id',
            'collaborator_id' => 'nullable|exists:collaborators,id',
            'target_university' => 'nullable|string|max:255',
            'major' => 'nullable|string|max:255',
            'intake_month' => 'nullable|integer|between:1,12',
            'program_type' => ['nullable', Rule::in(['REGULAR', 'PART_TIME'])],
            'source' => ['required', Rule::in(['form', 'ref', 'facebook', 'zalo', 'tiktok', 'hotline', 'event', 'school', 'walkin', 'other'])],
            'status' => ['required', Rule::in(['new', 'contacted', 'submitted', 'approved', 'enrolled', 'rejected', 'dropped', 'pending', 'interviewed', 'deposit_paid', 'offer_sent', 'offer_accepted'])],
            'notes' => 'nullable|string',
            'dob' => 'nullable|date|before:today',
            'address' => 'nullable|string|max:500',
        ];
    }
}
