<?php

namespace App\Filament\Resources\Intakes\Pages;

use App\Filament\Resources\Intakes\IntakeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntakes extends ListRecords {
    protected static string $resource = IntakeResource::class;

    public function mount(): void {
        parent::mount();
        session()->forget('intakes_show_trashed');
    }

  public function getTitle(): string {
        return '';
    }
    


    
}
