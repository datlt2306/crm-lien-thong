<?php

namespace App\Filament\Resources\PushTokens;

use App\Filament\Resources\PushTokens\Pages\CreatePushToken;
use App\Filament\Resources\PushTokens\Pages\EditPushToken;
use App\Filament\Resources\PushTokens\Pages\ListPushTokens;
use App\Filament\Resources\PushTokens\Schemas\PushTokenForm;
use App\Filament\Resources\PushTokens\Tables\PushTokensTable;
use App\Models\PushToken;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PushTokenResource extends Resource {
    protected static ?string $model = PushToken::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDevicePhoneMobile;

    protected static ?string $navigationLabel = 'Push Tokens';

    protected static ?string $modelLabel = 'Push Token';

    protected static ?string $pluralModelLabel = 'Push Tokens';

    public static function form(Schema $schema): Schema {
        return PushTokenForm::configure($schema);
    }

    public static function table(Table $table): Table {
        return PushTokensTable::configure($table);
    }

    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getPages(): array {
        return [
            'index' => ListPushTokens::route('/'),
            'create' => CreatePushToken::route('/create'),
            'edit' => EditPushToken::route('/{record}/edit'),
        ];
    }
}
