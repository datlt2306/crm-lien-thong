<?php

namespace App\Filament\Resources\DownlineCommissionConfigResource\Pages;

use App\Filament\Resources\DownlineCommissionConfigResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDownlineCommissionConfig extends CreateRecord {
    protected static string $resource = DownlineCommissionConfigResource::class;

    protected function getRedirectUrl(): string {
        return $this->getResource()::getUrl('index');
    }
}
