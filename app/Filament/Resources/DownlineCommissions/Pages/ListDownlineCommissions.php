<?php

namespace App\Filament\Resources\DownlineCommissions\Pages;

use App\Filament\Resources\DownlineCommissions\DownlineCommissionResource;
use Filament\Resources\Pages\ListRecords;

class ListDownlineCommissions extends ListRecords {
    protected static string $resource = DownlineCommissionResource::class;

    public function getTitle(): string {
        return 'Chia hoa hồng nội bộ';
    }

    protected function getHeaderActions(): array {
        return [];
    }
}
