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
    ];

    // Enum StudentStatus - Pipeline quản lý hành trình nhập học
    public const STATUS_NEW = 'new';                    // Mới
    public const STATUS_CONTACTED = 'contacted';        // Đã liên hệ
    public const STATUS_SUBMITTED = 'submitted';        // Đã nộp hồ sơ
    public const STATUS_APPROVED = 'approved';          // Đã duyệt
    public const STATUS_ENROLLED = 'enrolled';          // Đã nhập học
    public const STATUS_REJECTED = 'rejected';          // Từ chối

    public static function getStatusOptions(): array {
        return [
            self::STATUS_NEW => 'Mới',
            self::STATUS_CONTACTED => 'Đã liên hệ',
            self::STATUS_SUBMITTED => 'Đã nộp hồ sơ',
            self::STATUS_APPROVED => 'Đã duyệt',
            self::STATUS_ENROLLED => 'Đã nhập học',
            self::STATUS_REJECTED => 'Từ chối',
        ];
    }

    /**
     * Kiểm tra xem sinh viên có thể chuyển sang trạng thái tiếp theo không
     */
    public function canAdvanceToNextStatus(): bool {
        $nextStatuses = [
            self::STATUS_NEW => [self::STATUS_CONTACTED],
            self::STATUS_CONTACTED => [self::STATUS_SUBMITTED],
            self::STATUS_SUBMITTED => [self::STATUS_APPROVED, self::STATUS_REJECTED],
            self::STATUS_APPROVED => [self::STATUS_ENROLLED, self::STATUS_REJECTED],
            self::STATUS_ENROLLED => [],
            self::STATUS_REJECTED => [],
        ];

        return !empty($nextStatuses[$this->status] ?? []);
    }

    /**
     * Lấy danh sách trạng thái tiếp theo có thể chuyển đến
     */
    public function getNextAvailableStatuses(): array {
        $nextStatuses = [
            self::STATUS_NEW => [self::STATUS_CONTACTED],
            self::STATUS_CONTACTED => [self::STATUS_SUBMITTED],
            self::STATUS_SUBMITTED => [self::STATUS_APPROVED, self::STATUS_REJECTED],
            self::STATUS_APPROVED => [self::STATUS_ENROLLED, self::STATUS_REJECTED],
            self::STATUS_ENROLLED => [],
            self::STATUS_REJECTED => [],
        ];

        return $nextStatuses[$this->status] ?? [];
    }

    /**
     * Kiểm tra xem sinh viên đã hoàn thành quy trình chưa
     */
    public function isCompleted(): bool {
        return in_array($this->status, [self::STATUS_ENROLLED, self::STATUS_REJECTED]);
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
            'organization_id' => 'required|exists:organizations,id',
            'collaborator_id' => 'nullable|exists:collaborators,id',
            'target_university' => 'nullable|string|max:255',
            'major' => 'nullable|string|max:255',
            'intake_month' => 'nullable|integer|between:1,12',
            'program_type' => ['nullable', Rule::in(['REGULAR', 'PART_TIME'])],
            'source' => ['required', Rule::in(['form', 'ref', 'facebook', 'zalo', 'tiktok', 'hotline', 'event', 'school', 'walkin', 'other'])],
            'status' => ['required', Rule::in(['new', 'contacted', 'submitted', 'approved', 'enrolled', 'rejected', 'pending', 'interviewed', 'deposit_paid', 'offer_sent', 'offer_accepted'])],
            'notes' => 'nullable|string',
            'dob' => 'nullable|date|before:today',
            'address' => 'nullable|string|max:500',
        ];
    }
}
