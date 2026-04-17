<?php

namespace App\Filament\Resources\CollaboratorRegistrations\Pages;

use App\Filament\Resources\CollaboratorRegistrations\CollaboratorRegistrationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCollaboratorRegistration extends CreateRecord {
    protected static string $resource = CollaboratorRegistrationResource::class;

}
