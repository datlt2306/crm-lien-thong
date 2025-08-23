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

        // Super admin và chủ đơn vị có thể xem payments
        if (in_array($user->role, ['super_admin', 'chủ đơn vị'])) {
            return true;
        }

        // CTV có thể xem payments của mình
        if ($user->role === 'ctv') {
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
