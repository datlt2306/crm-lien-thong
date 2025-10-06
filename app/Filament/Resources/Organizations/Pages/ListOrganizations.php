<?php

namespace App\Filament\Resources\Organizations\Pages;

use App\Filament\Resources\Organizations\OrganizationResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Support\Facades\Auth;

class ListOrganizations extends ListRecords {
    protected static string $resource = OrganizationResource::class;

    public function getTitle(): string {
        return 'Danh sách đơn vị';
    }
    public function getBreadcrumb(): string {
        return 'Danh sách đơn vị';
    }

    public function mount(): void {
        $user = Auth::user();

        // Chủ đơn vị không được truy cập danh sách đơn vị
        if ($user?->role === 'chủ đơn vị') {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        parent::mount();
    }
    protected function getHeaderActions(): array {
        $actions = [];

        // Chỉ super admin mới có thể tạo đơn vị mới
        if (Auth::user()?->role === 'super_admin') {
            $actions[] = CreateAction::make()->label('Thêm đơn vị mới');
        }

        return $actions;
    }
}
