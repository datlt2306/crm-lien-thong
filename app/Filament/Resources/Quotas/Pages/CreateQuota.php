<?php

namespace App\Filament\Resources\Quotas\Pages;

use App\Filament\Resources\Quotas\QuotaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQuota extends CreateRecord {
    protected static string $resource = QuotaResource::class;

    public function mount(): void {
        $user = \Illuminate\Support\Facades\Auth::user();

        // Chỉ super_admin và organization_owner mới có thể truy cập
        if (!$user || !in_array($user->role, ['super_admin', 'organization_owner'])) {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        parent::mount();
    }
}
