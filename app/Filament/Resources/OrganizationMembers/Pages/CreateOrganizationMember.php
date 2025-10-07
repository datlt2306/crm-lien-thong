<?php

namespace App\Filament\Resources\OrganizationMembers\Pages;

use App\Filament\Resources\OrganizationMembers\OrganizationMemberResource;
use Filament\Resources\Pages\CreateRecord;

class CreateOrganizationMember extends CreateRecord
{
    protected static string $resource = OrganizationMemberResource::class;
}
