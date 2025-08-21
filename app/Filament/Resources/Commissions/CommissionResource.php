<?php

namespace App\Filament\Resources\Commissions;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Filament\Tables\Table;
use App\Models\Commission;
use App\Filament\Resources\Commissions\Pages\ListCommissions;

class CommissionResource extends Resource {
    protected static ?string $model = Commission::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Hoa hồng';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCurrencyDollar;

    public static function shouldRegisterNavigation(): bool {
        return Gate::allows('view_finance');
    }

    public static function form(Schema $schema): Schema {
        return $schema;
    }

    public static function table(Table $table): Table {
        return $table;
    }

    public static function getPages(): array {
        return [
            'index' => ListCommissions::route('/'),
        ];
    }
}
