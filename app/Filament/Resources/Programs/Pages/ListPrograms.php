<?php

namespace App\Filament\Resources\Programs\Pages;

use App\Filament\Resources\Programs\ProgramResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrograms extends ListRecords
{
    protected static string $resource = ProgramResource::class;

    public function mount(): void {
        parent::mount();
        session()->forget('programs_show_trashed');
    }


    


    


    


    

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         CreateAction::make()->label('Thêm chương trình'),
    //     ];
    // }
     public function getTitle(): string {
        return '';
    }
}

