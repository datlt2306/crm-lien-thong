<?php

namespace App\Filament\Resources\PermissionManagement;

use App\Filament\Resources\PermissionManagement\Pages\CreatePermissionManagement;
use App\Filament\Resources\PermissionManagement\Pages\EditPermissionManagement;
use App\Filament\Resources\PermissionManagement\Pages\ListPermissionManagement;
use App\Filament\Resources\PermissionManagement\Schemas\PermissionManagementForm;
use App\Filament\Resources\PermissionManagement\Tables\PermissionManagementTable;
use App\Models\PermissionManagement;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PermissionManagementResource extends Resource {
    protected static ?string $model = \Spatie\Permission\Models\Role::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;
    protected static ?string $navigationLabel = 'Phân quyền';
    protected static string|\UnitEnum|null $navigationGroup = 'Hệ thống';
    protected static ?int $navigationSort = 2;

    public static function shouldRegisterNavigation(): bool {
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user && $user->role === 'super_admin';
    }

    public static function form(Schema $schema): Schema {
        return PermissionManagementForm::configure($schema);
    }

    public static function table(Table $table): Table {
        return PermissionManagementTable::configure($table);
    }

    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getPages(): array {
        return [
            'index' => ListPermissionManagement::route('/'),
            'create' => CreatePermissionManagement::route('/create'),
            'edit' => EditPermissionManagement::route('/{record}/edit'),
        ];
    }
}
