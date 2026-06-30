<?php

namespace App\Filament\Resources\Commissions\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Commissions\CommissionResource;
use Illuminate\Support\Facades\Auth;
use App\Filament\Resources\Commissions\CommissionResource\Widgets\CommissionStatsWidget;

class ListCommissions extends ListRecords {
    protected static string $resource = CommissionResource::class;

    protected function getHeaderWidgets(): array {
        return [];
    }

    public static function canAccess(array $parameters = []): bool {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return $user->can('commission_view_any') || 
            in_array($user->role, ['super_admin', 'admin', 'organization_owner', 'accountant', 'collaborator', 'document']);
    }

    protected function getHeaderActions(): array {
        return [];
    }

    public function getTitle(): string {
        return '';
    }
}
