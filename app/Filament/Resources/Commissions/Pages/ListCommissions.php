<?php

namespace App\Filament\Resources\Commissions\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Commissions\CommissionResource;
use Illuminate\Support\Facades\Auth;

class ListCommissions extends ListRecords {
    protected static string $resource = CommissionResource::class;

    public static function canAccess(array $parameters = []): bool {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Super admin và organization_owner có thể xem commissions
        if (in_array($user->role, ['super_admin', 'organization_owner'])) {
            return true;
        }

        // CTV có thể xem commissions của mình
        if ($user->role === 'ctv') {
            return true;
        }

        // Accountant có thể xem commissions (để đối soát)
        if ($user->role === 'accountant') {
            return true;
        }

        return false;
    }

    public function getTitle(): string {
        return 'Danh sách hoa hồng';
    }

    public function getBreadcrumb(): string {
        return 'Danh sách hoa hồng';
    }
}
