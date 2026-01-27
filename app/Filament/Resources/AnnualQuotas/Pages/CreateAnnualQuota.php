<?php

namespace App\Filament\Resources\AnnualQuotas\Pages;

use App\Filament\Resources\AnnualQuotas\AnnualQuotaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateAnnualQuota extends CreateRecord {
    protected static string $resource = AnnualQuotaResource::class;

    public function mount(): void {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user || !in_array($user->role, ['super_admin', 'organization_owner'])) {
            abort(403, 'Bạn không có quyền truy cập.');
        }
        parent::mount();
    }
}
