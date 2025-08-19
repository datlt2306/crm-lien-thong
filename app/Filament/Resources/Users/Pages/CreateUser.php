<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord {
    protected static string $resource = UserResource::class;

    public function getTitle(): string {
        return 'Thêm người dùng mới';
    }
    public function getBreadcrumb(): string {
        return 'Thêm người dùng mới';
    }
}
