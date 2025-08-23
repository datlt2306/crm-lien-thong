<?php

namespace App\Filament\Resources\Collaborators\Pages;

use App\Filament\Resources\Collaborators\CollaboratorResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCollaborator extends ViewRecord
{
    protected static string $resource = CollaboratorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make()
                ->label('Chỉnh sửa cộng tác viên'),
        ];
    }

    public function getTitle(): string {
        return 'Chi tiết cộng tác viên';
    }

    public function getBreadcrumb(): string {
        return 'Chi tiết cộng tác viên';
    }
}
