<?php

namespace App\Filament\Resources\IntakeQuotas\Pages;

use App\Filament\Resources\IntakeQuotas\IntakeQuotaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIntakeQuota extends EditRecord
{
    protected static string $resource = IntakeQuotaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
