<?php

namespace App\Filament\Resources\AnnualQuotas\Pages;

use App\Filament\Resources\AnnualQuotas\AnnualQuotaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditAnnualQuota extends EditRecord {
    protected static string $resource = AnnualQuotaResource::class;

    public function mount(int|string $record): void {
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user || !in_array($user->role, ['super_admin', 'organization_owner'])) {
            abort(403, 'Bạn không có quyền truy cập.');
        }
        parent::mount($record);
    }

    protected function getHeaderActions(): array {
        return [DeleteAction::make()];
    }
}
