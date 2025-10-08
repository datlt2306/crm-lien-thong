<?php

namespace App\Filament\Resources\Intakes\Pages;

use App\Filament\Resources\Intakes\IntakeResource;
use Filament\Resources\Pages\CreateRecord;

class CreateIntake extends CreateRecord {
    protected static string $resource = IntakeResource::class;

    public function mount(): void {
        $user = \Illuminate\Support\Facades\Auth::user();

        // Chỉ super_admin và organization_owner mới có thể truy cập
        if (!$user || !in_array($user->role, ['super_admin', 'organization_owner'])) {
            abort(403, 'Bạn không có quyền truy cập trang này.');
        }

        parent::mount();
    }
}
