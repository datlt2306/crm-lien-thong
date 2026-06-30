<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListStudents extends ListRecords {
    protected static string $resource = StudentResource::class;

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    // public function getContentTabPosition(): \Filament\Resources\Pages\ListRecords\TabPosition
    // {
    //     return \Filament\Resources\Pages\ListRecords\TabPosition::Header;
    // }



    protected function getHeaderActions(): array {
        return [];
    }

    public function getTitle(): string {
        return 'Danh sách học viên';
    }
}
