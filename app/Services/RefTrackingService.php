<?php

namespace App\Services;

use Illuminate\Http\Request;
use App\Models\Collaborator;
use App\Models\RefCode;

class RefTrackingService {
    const REF_COOKIE_NAME = 'ref_id';
    const COOKIE_EXPIRY = 2592000; // 30 days in seconds

    /**
     * Lưu ref_id vào cookie khi user vào ref link
     */
    public function setRefCookie(Request $request, string $ref_id): void {
        // Kiểm tra ref_id có hợp lệ không (có thể từ RefCode hoặc trực tiếp từ Collaborator)
        $collaborator = $this->resolveCollaborator($ref_id);
        
        if (!$collaborator) {
            return;
        }

        // Set cookie với thời hạn 30 ngày (lưu mã proxy nguyên gốc)
        cookie()->queue(
            self::REF_COOKIE_NAME,
            $ref_id,
            self::COOKIE_EXPIRY / 60, // Convert to minutes
            '/',
            null,
            false, // secure
            false, // httpOnly
            false, // raw
            'Lax' // sameSite
        );
    }

    /**
     * Lấy ref_id từ cookie
     */
    public function getRefFromCookie(Request $request): ?string {
        return $request->cookie(self::REF_COOKIE_NAME);
    }

    /**
     * Xóa ref cookie
     */
    public function clearRefCookie(): void {
        cookie()->queue(cookie()->forget(self::REF_COOKIE_NAME));
    }

    /**
     * Lấy collaborator từ cookie hoặc ref_id
     */
    public function getCollaborator(Request $request, ?string $ref_id = null): ?Collaborator {
        // Ưu tiên ref_id từ URL, sau đó từ cookie
        $finalRefId = $ref_id ?: $this->getRefFromCookie($request);

        if (!$finalRefId) {
            return null;
        }

        return $this->resolveCollaborator($finalRefId);
    }
    
    /**
     * Resolves the actual collaborator record given a raw ref code.
     * It first checks the proxy `ref_codes` mapping. If not found,
     * it falls back to the original behavior of finding by `ref_id` on the Collaborator model.
     */
    public function resolveCollaborator(string $code): ?Collaborator {
        // Check proxy mapping table first
        $refCode = RefCode::where('code', $code)->first();
        if ($refCode && $refCode->collaborator) {
            return $refCode->collaborator->status === 'active' ? $refCode->collaborator : null;
        }
        
        // Fallback to direct Collaborator lookup
        return Collaborator::where('ref_id', $code)->where('status', 'active')->first();
    }
}
