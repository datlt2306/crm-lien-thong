<?php

namespace App\Filament\Resources\Majors\Pages;

use App\Filament\Resources\Majors\MajorResource;
use Filament\Resources\Pages\CreateRecord;

class CreateMajor extends CreateRecord {
    protected static string $resource = MajorResource::class;

    public function getTitle(): string {
        return 'Thêm ngành học mới';
    }

    public function getBreadcrumb(): string {
        return 'Thêm ngành học mới';
    }

    protected function getFormActions(): array {
        return [
            $this->getCreateFormAction()
                ->label('Tạo ngành học'),
            $this->getCancelFormAction()
                ->label('Hủy'),
        ];
    }
}
