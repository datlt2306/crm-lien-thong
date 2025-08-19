<?php

namespace App\Filament\Resources\Collaborators\Pages;

use App\Filament\Resources\Collaborators\CollaboratorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCollaborators extends ListRecords {
    protected static string $resource = CollaboratorResource::class;

    public function getTitle(): string {
        return 'Danh sách cộng tác viên';
    }
    public function getBreadcrumb(): string {
        return 'Danh sách cộng tác viên';
    }
    protected function getHeaderActions(): array {
        return [
            CreateAction::make()->label('Thêm cộng tác viên mới'),
        ];
    }
}
