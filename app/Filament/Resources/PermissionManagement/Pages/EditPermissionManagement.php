<?php

namespace App\Filament\Resources\PermissionManagement\Pages;

use App\Filament\Resources\PermissionManagement\PermissionManagementResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPermissionManagement extends EditRecord
{
    protected static string $resource = PermissionManagementResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
