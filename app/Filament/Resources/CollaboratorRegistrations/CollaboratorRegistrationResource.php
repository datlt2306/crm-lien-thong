<?php

namespace App\Filament\Resources\CollaboratorRegistrations;

use App\Filament\Resources\CollaboratorRegistrations\Pages\CreateCollaboratorRegistration;
use App\Filament\Resources\CollaboratorRegistrations\Pages\EditCollaboratorRegistration;
use App\Filament\Resources\CollaboratorRegistrations\Pages\ListCollaboratorRegistrations;
use App\Filament\Resources\CollaboratorRegistrations\Schemas\CollaboratorRegistrationForm;
use App\Filament\Resources\CollaboratorRegistrations\Tables\CollaboratorRegistrationsTable;
use App\Models\CollaboratorRegistration;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class CollaboratorRegistrationResource extends Resource {
    protected static ?string $model = CollaboratorRegistration::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserPlus;

    protected static ?string $navigationLabel = 'Đăng ký CTV';

    protected static ?string $modelLabel = 'Đăng ký Cộng tác viên';

    protected static ?string $pluralModelLabel = 'Đăng ký Cộng tác viên';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema {
        return CollaboratorRegistrationForm::configure($schema);
    }

    public static function table(Table $table): Table {
        return CollaboratorRegistrationsTable::configure($table);
    }

    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getPages(): array {
        return [
            'index' => ListCollaboratorRegistrations::route('/'),
            'create' => CreateCollaboratorRegistration::route('/create'),
            'edit' => EditCollaboratorRegistration::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string {
        return static::getModel()::status('pending')->count();
    }

    public static function getNavigationBadgeColor(): string|array|null {
        $count = static::getModel()::status('pending')->count();
        if ($count > 0) {
            return 'warning';
        }
        return null;
    }
}
