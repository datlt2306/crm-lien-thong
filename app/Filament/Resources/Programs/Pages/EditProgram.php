<?php

namespace App\Filament\Resources\Programs\Pages;

use App\Filament\Resources\Programs\ProgramResource;
use Filament\Resources\Pages\EditRecord;

class EditProgram extends EditRecord {
    protected static string $resource = ProgramResource::class;

    public function getTitle(): string {
        return 'Chỉnh sửa chương trình';
    }

    public function getBreadcrumb(): string {
        return 'Chỉnh sửa chương trình';
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
