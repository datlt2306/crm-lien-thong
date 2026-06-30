<?php

namespace App\Filament\Resources\CommissionPolicies\Pages;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;
use App\Models\CommissionPolicy;

use App\Filament\Resources\CommissionPolicies\CommissionPolicyResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListCommissionPolicies extends ListRecords {
    protected static string $resource = CommissionPolicyResource::class;

    public function mount(): void {
        parent::mount();
        session()->forget('commission_policies_show_trashed');
    }


    


    


    


    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    // protected function getHeaderActions(): array {
    //     return [
    //         CreateAction::make()
    //             ->label('Thêm chính sách mới'),
    //     ];
    // }



    public function getTitle(): string {
        return 'Cấu hình hoa hồng';
    }

    public function getHeading(): string {
        return '';
    }

    public function getBreadcrumb(): string {
        return 'Cấu hình hoa hồng';
    }
}
