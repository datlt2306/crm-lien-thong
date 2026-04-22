<?php

namespace App\Filament\Resources\AnnualQuotas;

use App\Filament\Resources\AnnualQuotas\Pages\CreateAnnualQuota;
use App\Filament\Resources\AnnualQuotas\Pages\EditAnnualQuota;
use App\Filament\Resources\AnnualQuotas\Pages\ListAnnualQuotas;
use App\Filament\Resources\AnnualQuotas\Schemas\AnnualQuotaForm;
use App\Filament\Resources\AnnualQuotas\Tables\AnnualQuotasTable;
use App\Models\AnnualQuota;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class AnnualQuotaResource extends Resource {
    protected static ?string $model = AnnualQuota::class;

    protected static ?string $slug = 'annual-quotas';

    protected static string|\BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static string|\UnitEnum|null $navigationGroup = 'Tuyển sinh';
    protected static ?string $navigationLabel = 'Chỉ tiêu năm';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema {
        return AnnualQuotaForm::configure($schema);
    }

    public static function table(Table $table): Table {
        return AnnualQuotasTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder {
        $q = parent::getEloquentQuery();
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return $q->whereNull('id');
        }
        
        if ($user->can('annual_quota_view_any')) {
            return $q;
        }

        return $q->whereNull('id');
    }

    public static function getPages(): array {
        return [
            'index' => ListAnnualQuotas::route('/'),
            'create' => CreateAnnualQuota::route('/create'),
            'edit' => EditAnnualQuota::route('/{record}/edit'),
        ];
    }

    public static function canViewAny(): bool {
        return \Illuminate\Support\Facades\Auth::user()?->can('annual_quota_view_any') ?? false;
    }

    public static function canCreate(): bool {
        return \Illuminate\Support\Facades\Auth::user()?->can('annual_quota_create') ?? false;
    }

    public static function canEdit($record): bool {
        return \Illuminate\Support\Facades\Auth::user()?->can('annual_quota_update') ?? false;
    }

    public static function canDelete($record): bool {
        return \Illuminate\Support\Facades\Auth::user()?->can('annual_quota_delete') ?? false;
    }
}
