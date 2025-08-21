<?php

namespace App\Filament\Resources\DownlineCommissionConfigResource\Pages;

use App\Filament\Resources\DownlineCommissionConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDownlineCommissionConfigs extends ListRecords {
    protected static string $resource = DownlineCommissionConfigResource::class;

    protected function getHeaderActions(): array {
        return [
            Actions\CreateAction::make()
                ->label('Thêm cấu hình mới'),
        ];
    }
}
