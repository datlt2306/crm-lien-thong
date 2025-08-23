<?php

namespace App\Filament\Resources\Programs\Pages;

use App\Filament\Resources\Programs\ProgramResource;
use Filament\Resources\Pages\CreateRecord;

class CreateProgram extends CreateRecord {
    protected static string $resource = ProgramResource::class;

    public function getTitle(): string {
        return 'Thêm chương trình mới';
    }

    public function getBreadcrumb(): string {
        return 'Thêm chương trình mới';
    }

    protected function getFormActions(): array {
        return [
            $this->getCreateFormAction()
                ->label('Tạo chương trình'),
            $this->getCancelFormAction()
                ->label('Hủy'),
        ];
    }
}
