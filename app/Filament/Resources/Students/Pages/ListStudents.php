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



    public function getContentTabPosition(): \Filament\Resources\Pages\ListRecords\TabPosition
    {
        return \Filament\Resources\Pages\ListRecords\TabPosition::Header;
    }

    // Tiêm CSS để đẩy tab sang phải
    public function getTabs(): array
    {
        return [
            'active' => Tab::make('Học viên')
                ->modifyQueryUsing(fn (Builder $query) => $query->whereNull('students.deleted_at'))
                ->badge(fn() => \App\Models\Student::whereNull('deleted_at')->count())
                ->badgeColor('success'),
            'trash' => Tab::make('Thùng rác')
                ->icon('heroicon-o-trash')
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed())
                ->badge(fn() => \App\Models\Student::onlyTrashed()->count())
                ->badgeColor('danger'),
        ];
    }

    protected function getHeaderActions(): array {
        $actions = [];

        if (Auth::user()?->can('student_create')) {
            $actions[] = CreateAction::make()
                ->label('Thêm học viên mới');
        }

        return $actions;
    }

    public function getTitle(): string {
        return 'Danh sách học viên';
    }

    public function getBreadcrumb(): string {
        return 'Danh sách học viên';
    }
}
