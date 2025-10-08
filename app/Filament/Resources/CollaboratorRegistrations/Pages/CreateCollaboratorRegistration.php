<?php

namespace App\Filament\Resources\CollaboratorRegistrations\Pages;

use App\Filament\Resources\CollaboratorRegistrations\CollaboratorRegistrationResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateCollaboratorRegistration extends CreateRecord {
    protected static string $resource = CollaboratorRegistrationResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array {
        // Tự động gán organization_id cho organization_owner
        if (Auth::user()->role === 'organization_owner') {
            $organization = \App\Models\Organization::where('organization_owner_id', Auth::user()->id)->first();
            if ($organization) {
                $data['organization_id'] = $organization->id;
            }
        }

        return $data;
    }
}
