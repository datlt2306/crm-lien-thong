<?php

namespace App\Filament\Resources\Intakes\Pages;

use App\Filament\Resources\Intakes\IntakeResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListIntakes extends ListRecords {
    protected static string $resource = IntakeResource::class;

    protected function getHeaderActions(): array {
        $actions = [];

        $user = \Illuminate\Support\Facades\Auth::user();

        // Chỉ super_admin và organization_owner mới có thể create
        if ($user && in_array($user->role, ['super_admin', 'organization_owner'])) {
            $actions[] = CreateAction::make();
        }

        return $actions;
    }
}
