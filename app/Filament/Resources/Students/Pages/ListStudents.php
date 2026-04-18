<?php

namespace App\Filament\Resources\Students\Pages;

use App\Filament\Resources\Students\StudentResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

use Filament\Schemas\Components\Tabs\Tab;
use Illuminate\Database\Eloquent\Builder;

class ListStudents extends ListRecords {
    protected static string $resource = StudentResource::class;

    public function getTabs(): array
    {
        $user = Auth::user();
        if (!$user || $user->role !== 'super_admin') {
            return [];
        }

        return [
            'active' => Tab::make('Đang hoạt động')
                ->modifyQueryUsing(fn (Builder $query) => $query->withoutTrashed()),
            'trash' => Tab::make('Thùng rác')
                ->modifyQueryUsing(fn (Builder $query) => $query->onlyTrashed())
                ->icon('heroicon-m-trash'),
        ];
    }

    protected function getHeaderActions(): array {
        $actions = [];

        // Super_admin, organization_owner, CTV và kế toán đều có thể tạo mới
        if (in_array(Auth::user()?->role, ['super_admin', 'ctv', 'accountant'])) {
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
