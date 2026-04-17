<?php

namespace App\Filament\Resources\Quotas\Pages;

use App\Filament\Resources\Quotas\QuotaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListQuotas extends ListRecords {
    protected static string $resource = QuotaResource::class;

    protected function getHeaderActions(): array {
        $actions = [];
        $user = \Illuminate\Support\Facades\Auth::user();

        if ($user && in_array($user->role, ['super_admin', ])) {
            $actions[] = CreateAction::make();
        }

        return $actions;
    }
}
