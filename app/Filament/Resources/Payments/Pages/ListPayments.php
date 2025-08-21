<?php

namespace App\Filament\Resources\Payments\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Payments\PaymentResource;
use Illuminate\Support\Facades\Gate;

class ListPayments extends ListRecords {
    protected static string $resource = PaymentResource::class;

    public static function canAccess(array $parameters = []): bool {
        return Gate::allows('view_finance');
    }
}
