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

    protected function getHeaderWidgets(): array
    {
        return [];
    }

    public function getContentTabPosition(): \Filament\Resources\Pages\ListRecords\TabPosition
    {
        return \Filament\Resources\Pages\ListRecords\TabPosition::Header;
    }

    // Tiêm CSS để đẩy tab sang phải
    public function getTabs(): array
    {
        $user = Auth::user();
        // Ẩn tab đối với CTV để họ thấy tất cả học viên của mình trong một danh sách duy nhất
        if (!$user || !in_array($user->role, ['super_admin', 'admin', 'organization_owner', 'admissions', 'document', 'accountant'])) {
            return [];
        }

        return [
            'active' => Tab::make('Đang hoạt động')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', true)),
            'disabled' => Tab::make('Ngừng hoạt động')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('is_active', false)),
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
