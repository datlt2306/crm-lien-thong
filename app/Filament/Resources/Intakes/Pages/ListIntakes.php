<?php

namespace App\Filament\Resources\Intakes\Pages;

use App\Filament\Resources\Intakes\IntakeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntakes extends ListRecords {
    protected static string $resource = IntakeResource::class;

    public function getTabs(): array
    {
        return [
            'active' => \Filament\Schemas\Components\Tabs\Tab::make('Đợt tuyển sinh')
                ->modifyQueryUsing(fn ($query) => $query->whereNull('quotas.deleted_at'))
                ->badge(fn() => \App\Models\Intake::whereNull('deleted_at')->count())
                ->badgeColor('success'),
            'trash' => \Filament\Schemas\Components\Tabs\Tab::make('Thùng rác')
                ->icon('heroicon-o-trash')
                ->modifyQueryUsing(fn ($query) => $query->onlyTrashed())
                ->badge(fn() => \App\Models\Intake::onlyTrashed()->count())
                ->badgeColor('danger'),
        ];
    }
}
