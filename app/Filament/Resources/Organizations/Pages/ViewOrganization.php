<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Filament\Resources\Organizations\OrganizationResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewOrganization extends ViewRecord
{
    protected static string $resource = OrganizationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Chỉnh sửa đơn vị'),
        ];
    }

    public function getTitle(): string {
        return 'Chi tiết đơn vị';
    }

    public function getBreadcrumb(): string {
        return 'Chi tiết đơn vị';
    }
}
