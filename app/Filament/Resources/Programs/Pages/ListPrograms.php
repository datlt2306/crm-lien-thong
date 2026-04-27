<?php

namespace App\Filament\Resources\Programs\Pages;

use App\Filament\Resources\Programs\ProgramResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPrograms extends ListRecords
{
    protected static string $resource = ProgramResource::class;

    public function getTabs(): array
    {
        return [
            'active' => \Filament\Schemas\Components\Tabs\Tab::make('Chương trình')
                ->modifyQueryUsing(fn ($query) => $query->whereNull('programs.deleted_at'))
                ->badge(fn() => \App\Models\Program::whereNull('deleted_at')->count())
                ->badgeColor('success'),
            'trash' => \Filament\Schemas\Components\Tabs\Tab::make('Thùng rác')
                ->icon('heroicon-o-trash')
                ->modifyQueryUsing(fn ($query) => $query->onlyTrashed())
                ->badge(fn() => \App\Models\Program::onlyTrashed()->count())
                ->badgeColor('danger'),
        ];
    }
}

