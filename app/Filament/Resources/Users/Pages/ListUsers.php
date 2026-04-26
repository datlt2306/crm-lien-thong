<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords {
    protected static string $resource = UserResource::class;

    public function getTitle(): string {
        return 'Danh sách người dùng';
    }
    public function getBreadcrumb(): string {
        return 'Danh sách người dùng';
    }
    public function getTabs(): array
    {
        return [
            'all' => \Filament\Schemas\Components\Tabs\Tab::make('Tất cả người dùng')
                ->badge(fn() => \App\Models\User::whereNull('deleted_at')->count()),
            'trash' => \Filament\Schemas\Components\Tabs\Tab::make('Thùng rác')
                ->icon('heroicon-o-trash')
                ->modifyQueryUsing(fn ($query) => $query->onlyTrashed())
                ->badge(fn() => \App\Models\User::onlyTrashed()->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderActions(): array {
        return [
            CreateAction::make()->label('Thêm người dùng mới'),
        ];
    }
}
