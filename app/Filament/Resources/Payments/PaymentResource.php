<?php

namespace App\Filament\Resources\Payments;

use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Gate;
use Filament\Tables\Table;
use App\Models\Payment;
use App\Filament\Resources\Payments\Pages\ListPayments;

class PaymentResource extends Resource {
    protected static ?string $model = Payment::class;
    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Thanh toán';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

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
            'index' => ListPayments::route('/'),
        ];
    }
}
