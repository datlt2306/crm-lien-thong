<?php

namespace App\Filament\Resources\NotificationPreferences;

use App\Filament\Resources\NotificationPreferences\Pages\CreateNotificationPreference;
use App\Filament\Resources\NotificationPreferences\Pages\EditNotificationPreference;
use App\Filament\Resources\NotificationPreferences\Pages\ListNotificationPreferences;
use App\Filament\Resources\NotificationPreferences\Schemas\NotificationPreferenceForm;
use App\Filament\Resources\NotificationPreferences\Tables\NotificationPreferencesTable;
use App\Models\NotificationPreference;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class NotificationPreferenceResource extends Resource {
    protected static ?string $model = NotificationPreference::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBell;

    protected static ?string $navigationLabel = 'Cài đặt thông báo';
    protected static string|\UnitEnum|null $navigationGroup = 'Hệ thống';
    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Cài đặt thông báo';

    protected static ?string $pluralModelLabel = 'Cài đặt thông báo';

    public static function form(Schema $schema): Schema {
        return NotificationPreferenceForm::configure($schema);
    }

    public static function table(Table $table): Table {
        return NotificationPreferencesTable::configure($table);
    }

    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getPages(): array {
        return [
            'index' => ListNotificationPreferences::route('/'),
            'create' => CreateNotificationPreference::route('/create'),
            'edit' => EditNotificationPreference::route('/{record}/edit'),
        ];
    }
}
