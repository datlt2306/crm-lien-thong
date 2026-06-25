<?php

namespace App\Filament\Resources\NotificationPreferences\Pages;

use App\Filament\Resources\NotificationPreferences\NotificationPreferenceResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListNotificationPreferences extends ListRecords
{
    protected static string $resource = NotificationPreferenceResource::class;
    
    public function getTitle(): string
    {
        return 'Cài đặt thông báo';
    }

    public function getHeading(): string
    {
        return '';
    }

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         CreateAction::make(),
    //     ];
    // }
}
