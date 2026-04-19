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
        if (in_array($user->role, ['super_admin', 'accountant', 'ctv'])) {
            return true;
        }

        return false;
    }

    protected function getHeaderWidgets(): array {
        return [
            CommissionResource\Widgets\CommissionSummary::class,
        ];
    }

    public function getMaxContentWidth(): string {
        return 'full';
    }

    public function getTitle(): string {
        return 'Báo cáo hoa hồng & Đối soát';
    }

    public function getBreadcrumb(): string {
        return 'Hoa hồng';
    }
}
