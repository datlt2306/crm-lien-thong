<?php

namespace App\Filament\Resources\Majors\Pages;

use App\Filament\Resources\Majors\MajorResource;
use Filament\Resources\Pages\EditRecord;

class EditMajor extends EditRecord {
    protected static string $resource = MajorResource::class;

    public function getTitle(): string {
        return 'Chỉnh sửa ngành học';
    }

    public function getBreadcrumb(): string {
        return 'Chỉnh sửa ngành học';
    }

    protected function getFormActions(): array {
        return [
            $this->getSaveFormAction()
                ->label('Lưu thay đổi'),
            $this->getCancelFormAction()
                ->label('Hủy'),
        ];
    }
}
