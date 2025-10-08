<?php

namespace App\Filament\Resources\IntakeQuotas\Pages;

use App\Filament\Resources\IntakeQuotas\IntakeQuotaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntakeQuotas extends ListRecords
{
    protected static string $resource = IntakeQuotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
