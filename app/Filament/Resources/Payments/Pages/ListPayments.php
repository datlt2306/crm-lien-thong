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

        // Cho phép truy cập nếu có quyền xem danh sách thanh toán
        return $user->can('payment_view_any');
    }

    public function getTitle(): string {
        return 'Danh sách thanh toán';
    }

    public function getBreadcrumb(): string {
        return 'Danh sách thanh toán';
    }
}
