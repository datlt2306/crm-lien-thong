<?php

namespace App\Filament\Resources\CommissionPolicies;

use App\Filament\Resources\CommissionPolicies\Pages\CreateCommissionPolicy;
use App\Filament\Resources\CommissionPolicies\Pages\EditCommissionPolicy;
use App\Filament\Resources\CommissionPolicies\Pages\ListCommissionPolicies;
use App\Filament\Resources\CommissionPolicies\Schemas\CommissionPolicyForm;
use App\Filament\Resources\CommissionPolicies\Schemas\CommissionPolicyInfolist;
use App\Filament\Resources\CommissionPolicies\Tables\CommissionPoliciesTable;
use App\Models\CommissionPolicy;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Gate;

class CommissionPolicyResource extends Resource {
    protected static ?string $model = CommissionPolicy::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Finance';
    protected static ?string $navigationLabel = 'Cấu hình hoa hồng';
    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    public static function shouldRegisterNavigation(): bool {
        return Gate::allows('manage_commission');
    }

    public static function form(Schema $schema): Schema {
        return CommissionPolicyForm::configure($schema);
    }

    public static function table(Table $table): Table {
        return CommissionPoliciesTable::configure($table);
    }

    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getPages(): array {
        return [
            'index' => ListCommissionPolicies::route('/'),
            'create' => CreateCommissionPolicy::route('/create'),
            'edit' => EditCommissionPolicy::route('/{record}/edit'),
        ];
    }
}
