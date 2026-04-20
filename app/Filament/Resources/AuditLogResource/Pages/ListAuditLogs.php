<?php

namespace App\Filament\Resources\AuditLogResource\Pages;

use App\Filament\Resources\AuditLogResource;
use Filament\Resources\Pages\ListRecords;
use Filament\Actions\Action;
use App\Exports\AuditLogExport;
use Maatwebsite\Excel\Facades\Excel;

class ListAuditLogs extends ListRecords
{
    protected static string $resource = AuditLogResource::class;

    public static function canAccess(array $parameters = []): bool
    {
        return static::getResource()::canAccess($parameters);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('view_timeline')
                ->label('Xem Dòng thời gian')
                ->icon('heroicon-o-clock')
                ->color('info')
                ->url(fn (): string => AuditTimeline::getUrl()),
            Action::make('export_excel')
                ->label('Xuất Excel')
                ->icon('heroicon-o-arrow-down-tray')
                ->color('success')
                ->action(fn () => Excel::download(
                    new AuditLogExport($this->getFilteredTableQuery()),
                    'nhat-ky-he-thong-' . now()->format('Y-m-d-His') . '.xlsx'
                )),
        ];
    }
}
