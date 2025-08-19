<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Filament\Resources\Organizations\OrganizationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListOrganizations extends ListRecords {
    protected static string $resource = OrganizationResource::class;

    public function getTitle(): string {
        return 'Danh sách đơn vị';
    }
    public function getBreadcrumb(): string {
        return 'Danh sách đơn vị';
    }
    protected function getHeaderActions(): array {
        return [
            CreateAction::make()->label('Thêm đơn vị mới'),
        ];
    }
}
