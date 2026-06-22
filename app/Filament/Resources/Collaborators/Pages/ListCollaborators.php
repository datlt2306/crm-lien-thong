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
            'active' => \Filament\Schemas\Components\Tabs\Tab::make('Cộng tác viên')
                ->modifyQueryUsing(fn ($query) => $query->whereNull('collaborators.deleted_at'))
                ->badge(fn() => \App\Models\Collaborator::whereNull('deleted_at')->count())
                ->badgeColor('success'),
            'trash' => \Filament\Schemas\Components\Tabs\Tab::make('Thùng rác')
                ->icon('heroicon-o-trash')
                ->modifyQueryUsing(fn ($query) => $query->onlyTrashed())
                ->badge(fn() => \App\Models\Collaborator::onlyTrashed()->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderActions(): array {
        return [
            CreateAction::make()->label('Thêm cộng tác viên mới'),
        ];
    }
}
