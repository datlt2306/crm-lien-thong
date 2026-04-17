<?php

namespace App\Filament\Resources\AnnualQuotas\Pages;

use App\Filament\Resources\AnnualQuotas\AnnualQuotaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAnnualQuotas extends ListRecords {
    protected static string $resource = AnnualQuotaResource::class;

    protected function getHeaderActions(): array {
        $user = \Illuminate\Support\Facades\Auth::user();
        if ($user && in_array($user->role, ['super_admin', ])) {
            return [CreateAction::make()->label('Thêm chỉ tiêu năm')];
        }
        return [];
    }
}
