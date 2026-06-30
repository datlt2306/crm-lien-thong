<?php

namespace App\Filament\Resources\Majors\Pages;

use App\Filament\Resources\Majors\MajorResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMajors extends ListRecords
{
    protected static string $resource = MajorResource::class;

    public function mount(): void {
        parent::mount();
        session()->forget('majors_show_trashed');
    }


    


    


    


    

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         CreateAction::make()->label('Thêm ngành học'),
    //     ];
    // }

    public function getTitle(): string {
        return '';
    }
}

