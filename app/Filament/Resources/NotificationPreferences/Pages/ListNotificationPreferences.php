<?php

namespace App\Filament\Resources\NotificationPreferences\Pages;

use App\Filament\Resources\NotificationPreferences\NotificationPreferenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotificationPreferences extends ListRecords
{
    protected static string $resource = NotificationPreferenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
