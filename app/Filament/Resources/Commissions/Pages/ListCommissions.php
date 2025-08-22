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

        // Super admin và chủ đơn vị có thể xem commissions
        if (in_array($user->role, ['super_admin', 'chủ đơn vị'])) {
            return true;
        }

        // CTV có thể xem commissions của mình
        if ($user->role === 'ctv') {
            return true;
        }

        return false;
    }
}
