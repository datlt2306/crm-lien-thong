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
    public function getTabs(): array
    {
        return [
            'active' => \Filament\Schemas\Components\Tabs\Tab::make('Đang hoạt động')
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', true)),
            'disabled' => \Filament\Schemas\Components\Tabs\Tab::make('Ngừng hoạt động')
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', false))
                ->icon('heroicon-m-no-symbol'),
        ];
    }

    protected function getHeaderActions(): array {
        return [
            CreateAction::make()->label('Thêm cộng tác viên mới'),
        ];
    }
}
