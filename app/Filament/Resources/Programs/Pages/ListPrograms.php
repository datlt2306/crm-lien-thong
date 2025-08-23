<?php

namespace App\Filament\Resources\Programs\Pages;

use App\Filament\Resources\Programs\ProgramResource;
use Filament\Resources\Pages\ListRecords;

class ListPrograms extends ListRecords {
    protected static string $resource = ProgramResource::class;

    public function getTitle(): string {
        return 'Danh sách chương trình';
    }

    public function getBreadcrumb(): string {
        return 'Danh sách chương trình';
    }

    protected function getHeaderActions(): array {
        return [
            \Filament\Actions\CreateAction::make()
                ->label('Thêm chương trình mới'),
        ];
    }
}
