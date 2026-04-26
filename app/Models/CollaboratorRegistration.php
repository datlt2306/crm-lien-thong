<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Notifications\CollaboratorRegistrationApprovedNotification;
use App\Notifications\CollaboratorRegistrationRejectedNotification;

class CollaboratorRegistration extends Model {
    use HasFactory;

    protected $fillable = [
        'full_name',
        'phone',
        'email',
        'ref_id',
        'password',
        'note',
        'status',
        'rejection_reason',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'reviewed_at' => 'datetime',
        'status' => 'string',
    ];


    /**
     * Quan hệ: Admin đã review
     */
    public function reviewer(): BelongsTo {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * Kiểm tra trạng thái pending
     */
    public function isPending(): bool {
        return $this->status === 'pending';
    }

    /**
     * Kiểm tra trạng thái approved
     */
    public function isApproved(): bool {
        return $this->status === 'approved';
    }

    /**
     * Kiểm tra trạng thái rejected
     */
    public function isRejected(): bool {
        return $this->status === 'rejected';
    }

    /**
     * Approve đăng ký và tạo cộng tác viên
     */
    public function approve(User $reviewer): bool {
        try {
            $this->status = 'approved';
            $this->reviewed_by = $reviewer->id;
            $this->reviewed_at = now();

            // Sinh ref_id nếu chưa có
            if (empty($this->ref_id)) {
                do {
                    $ref = strtoupper(substr(bin2hex(random_bytes(8)), 0, 8));
                } while (
                    Collaborator::where('ref_id', $ref)->exists() ||
                    CollaboratorRegistration::where('ref_id', $ref)->exists()
                );
                $this->ref_id = $ref;
            }

            $this->save();

            // Tạo cộng tác viên từ đăng ký
            $collaborator = Collaborator::create([
                'full_name' => $this->full_name,
                'phone' => $this->phone,
                'email' => $this->email,
                'ref_id' => $this->ref_id,
                'note' => $this->note,
                'status' => 'active',
            ]);

            // Tạo wallet cho cộng tác viên mới
            $collaborator->wallet()->create([
                'balance' => 0,
                'currency' => 'VND',
            ]);

            // Tự động sinh User Account cho CTV đăng nhập nếu có email
            if (!empty($this->email) && !empty($this->password)) {
                $user = \App\Models\User::where('email', $this->email)->first();
                if (!$user) {
                    \App\Models\User::create([
                        'name' => $this->full_name,
                        'email' => $this->email,
                        'phone' => $this->phone,
                        'password' => $this->password, // Đã hash trước lúc lưu
                        'role' => 'collaborator',
                    ]);
                }
            }

            // Gửi notification cho người đăng ký (nếu có email)
            if ($this->email) {
                try {
                    \Notification::route('mail', $this->email)
                        ->notify(new CollaboratorRegistrationApprovedNotification($this));
                } catch (\Exception $e) {
                    \Log::warning('Không thể gửi email notification cho đăng ký đã duyệt: ' . $e->getMessage());
                }
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Lỗi khi approve đăng ký cộng tác viên: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reject đăng ký
     */
    public function reject(User $reviewer, string $reason = null): bool {
        try {
            $this->status = 'rejected';
            $this->reviewed_by = $reviewer->id;
            $this->reviewed_at = now();
            $this->rejection_reason = $reason;
            $this->save();

            // Gửi notification cho người đăng ký (nếu có email)
            if ($this->email) {
                try {
                    \Notification::route('mail', $this->email)
                        ->notify(new CollaboratorRegistrationRejectedNotification($this));
                } catch (\Exception $e) {
                    \Log::warning('Không thể gửi email notification cho đăng ký bị từ chối: ' . $e->getMessage());
                }
            }

            return true;
        } catch (\Exception $e) {
            \Log::error('Lỗi khi reject đăng ký cộng tác viên: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Scope: Lọc theo trạng thái
     */
    public function scopeStatus($query, string $status) {
        return $query->where('status', $status);
    }

}
