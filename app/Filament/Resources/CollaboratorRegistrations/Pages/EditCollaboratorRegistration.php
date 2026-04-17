<?php

namespace App\Filament\Resources\CollaboratorRegistrations\Pages;

use App\Filament\Resources\CollaboratorRegistrations\CollaboratorRegistrationResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditCollaboratorRegistration extends EditRecord {
    protected static string $resource = CollaboratorRegistrationResource::class;

    protected function getHeaderActions(): array {
        return [
            DeleteAction::make(),
        ];
    }

}
