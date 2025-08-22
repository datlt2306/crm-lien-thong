<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListStudents extends ListRecords {
    protected static string $resource = StudentResource::class;

    protected function getHeaderActions(): array {
        $actions = [];

        // Chỉ super_admin và chủ đơn vị mới có thể tạo mới
        if (in_array(Auth::user()?->role, ['super_admin', 'chủ đơn vị'])) {
            $actions[] = CreateAction::make();
        }

        return $actions;
    }
}
