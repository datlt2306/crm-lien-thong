<?php

namespace App\Services;

use App\Models\Collaborator;
use Illuminate\Validation\ValidationException;

class CollaboratorValidationService {
    /**
     * Validate email uniqueness for collaborator creation
     */
    public static function validateEmailForCreation(string $email): void {
        if (!empty($email) && Collaborator::where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => ['Email đã được sử dụng bởi CTV khác.'],
            ]);
        }
    }

    /**
     * Validate email uniqueness for collaborator update
     */
    public static function validateEmailForUpdate(string $email, int $excludeId = null): void {
        if (!empty($email)) {
            $query = Collaborator::where('email', $email);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if ($query->exists()) {
                throw ValidationException::withMessages([
                    'email' => ['Email đã được sử dụng bởi CTV khác.'],
                ]);
            }
        }
    }

    /**
     * Validate phone uniqueness for collaborator creation
     */
    public static function validatePhoneForCreation(string $phone): void {
        if (empty($phone)) {
            throw ValidationException::withMessages([
                'phone' => ['Số điện thoại là bắt buộc cho CTV.'],
            ]);
        }

        if (Collaborator::where('phone', $phone)->exists()) {
            throw ValidationException::withMessages([
                'phone' => ['Số điện thoại đã được sử dụng.'],
            ]);
        }
    }

    /**
     * Validate phone uniqueness for collaborator update
     */
    public static function validatePhoneForUpdate(string $phone, int $excludeId = null): void {
        if (!empty($phone)) {
            $query = Collaborator::where('phone', $phone);
            if ($excludeId) {
                $query->where('id', '!=', $excludeId);
            }

            if ($query->exists()) {
                throw ValidationException::withMessages([
                    'phone' => ['Số điện thoại đã được sử dụng.'],
                ]);
            }
        }
    }

    /**
     * Validate both email and phone for collaborator creation
     */
    public static function validateForCreation(string $email, string $phone): void {
        self::validateEmailForCreation($email);
        self::validatePhoneForCreation($phone);
    }

    /**
     * Validate both email and phone for collaborator update
     */
    public static function validateForUpdate(string $email, string $phone, int $excludeId = null): void {
        self::validateEmailForUpdate($email, $excludeId);
        if (!empty($phone)) {
            self::validatePhoneForUpdate($phone, $excludeId);
        }
    }
}
