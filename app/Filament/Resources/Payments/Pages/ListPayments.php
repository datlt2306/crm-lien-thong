<?php

namespace App\Filament\Resources\Payments\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Payments\PaymentResource;
use Illuminate\Support\Facades\Auth;

class ListPayments extends ListRecords {
    protected static string $resource = PaymentResource::class;

    public static function canAccess(array $parameters = []): bool {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super admin, admin và organization_owner có thể xem payments
        if (in_array($user->role, ['super_admin', 'admin', 'organization_owner'])) {
            return true;
        }

        // CTV có thể xem payments của mình
        if ($user->role === 'ctv') {
            return true;
        }

        // Kế toán có quyền xem để xử lý phiếu thu
        if ($user->role === 'accountant') {
            return true;
        }

        return false;
    }

    public function getTitle(): string {
        return 'Danh sách thanh toán';
    }

    public function getBreadcrumb(): string {
        return 'Danh sách thanh toán';
    }
}
