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

        // Chỉ super_admin và organization_owner mới có thể chỉnh sửa
        if (in_array(Auth::user()?->role, ['super_admin', 'organization_owner'])) {
            $actions[] = EditAction::make()
                ->label('Chỉnh sửa học viên');
        }

        return $actions;
    }

    public function getTitle(): string {
        return 'Chi tiết học viên';
    }

    public function getBreadcrumb(): string {
        return 'Chi tiết học viên';
    }
}
