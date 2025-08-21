<?php

namespace App\Filament\Resources\DownlineCommissionConfigResource\Pages;

use App\Filament\Resources\DownlineCommissionConfigResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDownlineCommissionConfig extends EditRecord {
    protected static string $resource = DownlineCommissionConfigResource::class;

    protected function getHeaderActions(): array {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function getRedirectUrl(): string {
        return $this->getResource()::getUrl('index');
    }
}
