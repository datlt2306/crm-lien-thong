<?php

namespace App\Filament\Resources\PermissionManagement\Pages;

use App\Filament\Resources\PermissionManagement\PermissionManagementResource;
use Filament\Resources\Pages\CreateRecord;

class CreatePermissionManagement extends CreateRecord
{
    protected static string $resource = PermissionManagementResource::class;

    protected function afterCreate(): void
    {
        $data = $this->form->getState();
        $permissionIds = [];

        foreach ($data as $key => $value) {
            if (str_starts_with($key, 'perms_') && is_array($value)) {
                $permissionIds = array_merge($permissionIds, $value);
            }
        }

        $this->record->syncPermissions($permissionIds);
    }
}
