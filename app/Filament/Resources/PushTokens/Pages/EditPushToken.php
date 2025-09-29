<?php

namespace App\Filament\Resources\PushTokens\Pages;

use App\Filament\Resources\PushTokens\PushTokenResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPushToken extends EditRecord
{
    protected static string $resource = PushTokenResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
