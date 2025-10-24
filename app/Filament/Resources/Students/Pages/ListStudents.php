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

        // Super_admin, organization_owner, CTV và kế toán đều có thể tạo mới
        if (in_array(Auth::user()?->role, ['super_admin', 'organization_owner', 'ctv', 'accountant'])) {
            $actions[] = CreateAction::make()
                ->label('Thêm học viên mới');
        }

        return $actions;
    }

    public function getTitle(): string {
        return 'Danh sách học viên';
    }

    public function getBreadcrumb(): string {
        return 'Danh sách học viên';
    }
}
