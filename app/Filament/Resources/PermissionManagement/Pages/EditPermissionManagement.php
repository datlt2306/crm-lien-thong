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

    protected function afterSave(): void
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
