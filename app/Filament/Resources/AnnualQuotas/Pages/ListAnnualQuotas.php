<?php

namespace App\Filament\Resources\AnnualQuotas\Pages;

use App\Filament\Resources\AnnualQuotas\AnnualQuotaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAnnualQuotas extends ListRecords {
    protected static string $resource = AnnualQuotaResource::class;

    public function mount(): void {
        parent::mount();
        session()->forget('annual_quotas_show_trashed');
    }


    


    


    


    

    // protected function getHeaderActions(): array
    // {
    //     return [
    //         CreateAction::make()->label('Thêm chỉ tiêu năm'),
    //     ];
    // }
    public function getTitle(): string {
        return '';
    }
}
