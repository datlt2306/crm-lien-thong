<?php

namespace App\Filament\Resources\Majors\Pages;

use App\Filament\Resources\Majors\MajorResource;
use Filament\Resources\Pages\ListRecords;

class ListMajors extends ListRecords {
    protected static string $resource = MajorResource::class;

    public function getTitle(): string {
        return 'Danh sách ngành học';
    }

    public function getBreadcrumb(): string {
        return 'Danh sách ngành học';
    }

    protected function getHeaderActions(): array {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('Thêm ngành học mới'),
        ];
    }
}
