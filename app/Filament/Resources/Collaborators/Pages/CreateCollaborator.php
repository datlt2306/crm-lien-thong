<?php

namespace App\Filament\Resources\Collaborators\Pages;

use App\Filament\Resources\Collaborators\CollaboratorResource;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\Organization;

class CreateCollaborator extends CreateRecord {
    protected static string $resource = CollaboratorResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array {
        $user = Auth::user();
        if ($user->role === 'super_admin') {
            // Nếu là super_admin, cho phép chọn organization_id (không làm gì)
            return $data;
        }
        // Nếu là chủ tổ chức, tự động gán organization_id
        $org = Organization::where('owner_id', $user->id)->first();
        if ($org) {
            $data['organization_id'] = $org->id;
        }
        return $data;
    }

    public function getTitle(): string {
        return 'Thêm cộng tác viên mới';
    }
    public function getBreadcrumb(): string {
        return 'Thêm cộng tác viên mới';
    }
}
