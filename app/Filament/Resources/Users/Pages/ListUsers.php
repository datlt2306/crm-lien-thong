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
            'active' => \Filament\Schemas\Components\Tabs\Tab::make('Đang hoạt động')
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', true)),
            'disabled' => \Filament\Schemas\Components\Tabs\Tab::make('Ngừng hoạt động')
                ->modifyQueryUsing(fn ($query) => $query->where('is_active', false))
                ->icon('heroicon-m-no-symbol'),
        ];
    }

    protected function getHeaderActions(): array {
        return [
            CreateAction::make()->label('Thêm người dùng mới'),
        ];
    }
}
