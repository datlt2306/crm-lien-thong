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

    // public function getTabs(): array
    // {
    //     return [
    //         'active' => Tab::make('Học viên')
    //             ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('students.deleted_at'))
    //             ->badge(fn() => \App\Models\Student::whereNull('deleted_at')->count())
    //             ->badgeColor('success'),
    //         'trash' => Tab::make('Thùng rác')
    //             ->icon('heroicon-o-trash')
    //             ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed())
    //             ->badge(fn() => \App\Models\Student::onlyTrashed()->count())
    //             ->badgeColor('danger'),
    //     ];
    // }

    // protected function getHeaderActions(): array {
    //     return [];
    // }

    public function getTitle(): string {
        return '';
    }
}
