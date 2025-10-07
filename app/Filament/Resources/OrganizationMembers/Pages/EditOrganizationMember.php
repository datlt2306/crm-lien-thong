<?php

namespace App\Filament\Resources\OrganizationMembers\Pages;

use App\Filament\Resources\OrganizationMembers\OrganizationMemberResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditOrganizationMember extends EditRecord
{
    protected static string $resource = OrganizationMemberResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
