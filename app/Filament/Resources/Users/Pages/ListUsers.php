<?php

namespace App\Filament\Resources\Users\Pages;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

use App\Filament\Resources\Users\UserResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListUsers extends ListRecords {
    protected static string $resource = UserResource::class;

    public function mount(): void {
        parent::mount();
        session()->forget('users_show_trashed');
    }


    


    


    


    public function getTitle(): string {
        return 'Danh sách người dùng';
    }

    public function getHeading(): string {
        return '';
    }
    public function getBreadcrumb(): string {
        return 'Danh sách người dùng';
    }


    // protected function getHeaderActions(): array {
    //     return [
    //         CreateAction::make()->label('Thêm người dùng mới'),
    //     ];
    // }
}
