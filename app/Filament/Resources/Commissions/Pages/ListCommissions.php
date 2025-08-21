<?php

namespace App\Filament\Resources\Commissions\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Commissions\CommissionResource;
use Illuminate\Support\Facades\Gate;

class ListCommissions extends ListRecords {
    protected static string $resource = CommissionResource::class;

    public static function canAccess(array $parameters = []): bool {
        return Gate::allows('view_finance');
    }
}
