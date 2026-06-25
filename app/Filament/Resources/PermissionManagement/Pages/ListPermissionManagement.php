<?php

namespace App\Filament\Resources\PermissionManagement\Pages;

use App\Filament\Resources\PermissionManagement\PermissionManagementResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPermissionManagement extends ListRecords
{
    protected static string $resource = PermissionManagementResource::class;
    
    public function getTitle(): string
    {
        return 'Phân quyền';
    }

    public function getHeading(): string
    {
        return '';
    }

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         CreateAction::make(),
    //     ];
    // }
}
