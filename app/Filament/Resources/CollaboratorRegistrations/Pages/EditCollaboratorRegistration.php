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

    protected function mutateFormDataBeforeSave(array $data): array {
        // Đảm bảo organization_owner không thể thay đổi organization_id
        if (Auth::user()->role === 'organization_owner') {
            $organization = \App\Models\Organization::where('organization_owner_id', Auth::user()->id)->first();
            if ($organization) {
                $data['organization_id'] = $organization->id;
            }
        }

        return $data;
    }
}
