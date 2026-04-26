<?php

namespace App\Filament\Resources\AnnualQuotas\Pages;

use App\Filament\Resources\AnnualQuotas\AnnualQuotaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAnnualQuotas extends ListRecords {
    protected static string $resource = AnnualQuotaResource::class;

    public function getTabs(): array
    {
        return [
            'active' => \Filament\Schemas\Components\Tabs\Tab::make('Chỉ tiêu năm')
                ->modifyQueryUsing(fn ($query) => $query->whereNull('deleted_at'))
                ->badge(fn() => \App\Models\AnnualQuota::whereNull('deleted_at')->count())
                ->badgeColor('success'),
            'trash' => \Filament\Schemas\Components\Tabs\Tab::make('Thùng rác')
                ->icon('heroicon-o-trash')
                ->modifyQueryUsing(fn ($query) => $query->onlyTrashed())
                ->badge(fn() => \App\Models\AnnualQuota::onlyTrashed()->count())
                ->badgeColor('danger'),
        ];
    }
}
