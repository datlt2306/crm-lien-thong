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
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return false;
        }
        return in_array($user->role, ['super_admin', 'organization_owner', 'ctv']);
    }

    public static function form(Schema $schema): Schema {
        return AnnualQuotaForm::configure($schema);
    }

    public static function table(Table $table): Table {
        return AnnualQuotasTable::configure($table);
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder {
        $q = parent::getEloquentQuery()->with(['organization', 'major', 'program']);
        $user = \Illuminate\Support\Facades\Auth::user();
        if (!$user) {
            return $q->whereNull('id');
        }
        if ($user->role === 'super_admin') {
            return $q;
        }
        if ($user->role === 'organization_owner') {
            $org = \App\Models\Organization::where('organization_owner_id', $user->id)->first();
            return $org ? $q->where('organization_id', $org->id) : $q->whereNull('id');
        }
        if ($user->role === 'ctv') {
            $c = \App\Models\Collaborator::where('email', $user->email)->first();
            return ($c && $c->organization) ? $q->where('organization_id', $c->organization_id) : $q->whereNull('id');
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
        $u = \Illuminate\Support\Facades\Auth::user();
        return $u && in_array($u->role, ['super_admin', 'organization_owner', 'ctv']);
    }

    public static function canCreate(): bool {
        $u = \Illuminate\Support\Facades\Auth::user();
        return $u && in_array($u->role, ['super_admin', 'organization_owner']);
    }

    public static function canEdit($record): bool {
        $u = \Illuminate\Support\Facades\Auth::user();
        return $u && in_array($u->role, ['super_admin', 'organization_owner']);
    }

    public static function canDelete($record): bool {
        $u = \Illuminate\Support\Facades\Auth::user();
        return $u && in_array($u->role, ['super_admin', 'organization_owner']);
    }
}
