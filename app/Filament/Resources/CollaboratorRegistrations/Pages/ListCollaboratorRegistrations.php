<?php

namespace App\Filament\Resources\CollaboratorRegistrations\Pages;

use App\Filament\Resources\CollaboratorRegistrations\CollaboratorRegistrationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCollaboratorRegistrations extends ListRecords
{
    protected static string $resource = CollaboratorRegistrationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
