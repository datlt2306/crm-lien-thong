<?php

namespace App\Filament\Resources\AuditLogResource\Pages;

use App\Filament\Resources\AuditLogResource;
use Filament\Resources\Pages\ViewRecord;

class ViewAuditLog extends ViewRecord
{
    protected static string $resource = AuditLogResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return static::getResource()::canAccess($parameters);
    }

    protected static ?string $title = 'Chi tiết Nhật ký hệ thống';
}
