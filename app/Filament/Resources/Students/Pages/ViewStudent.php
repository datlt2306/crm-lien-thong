<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ViewStudent extends ViewRecord {
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array {
        $actions = [];

        // Chỉ super_admin và chủ đơn vị mới có thể chỉnh sửa
        if (in_array(Auth::user()?->role, ['super_admin', 'chủ đơn vị'])) {
            $actions[] = EditAction::make();
        }

        // Thêm action xem bill nếu có payment
        if ($this->record->payment && $this->record->payment->bill_path) {
            $actions[] = \Filament\Actions\Action::make('view_bill')
                ->label('Xem Bill')
                ->icon('heroicon-o-document-text')
                ->color('info')
                ->url(Storage::url($this->record->payment->bill_path))
                ->openUrlInNewTab();
        }

        return $actions;
    }
}
