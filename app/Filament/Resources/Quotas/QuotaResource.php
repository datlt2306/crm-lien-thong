<?php

namespace App\Filament\Resources\Quotas;

use App\Filament\Resources\Quotas\Pages\CreateQuota;
use App\Filament\Resources\Quotas\Pages\EditQuota;
use App\Filament\Resources\Quotas\Pages\ListQuotas;
use App\Filament\Resources\Quotas\Schemas\QuotaForm;
use App\Filament\Resources\Quotas\Tables\QuotasTable;
use App\Models\Quota;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class QuotaResource extends Resource {
    protected static ?string $model = Quota::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;
    protected static string|\UnitEnum|null $navigationGroup = 'Tuyển sinh';
    protected static ?string $navigationLabel = 'Đợt tuyển & Chỉ tiêu';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool {
        $user = \Illuminate\Support\Facades\Auth::user();

        if (!$user) {
            return false;
        }

        // Super admin, organization_owner và CTV đều có thể xem
        if (in_array($user->role, ['super_admin', 'organization_owner', 'ctv'])) {
            return true;
        }

        return false;
    }

    public static function form(Schema $schema): Schema {
        return QuotaForm::configure($schema);
    }

    public static function table(Table $table): Table {
        return QuotasTable::configure($table);
    }

    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder {
        $query = parent::getEloquentQuery();
        $user = \Illuminate\Support\Facades\Auth::user();

        if (!$user) {
            return $query->whereNull('id');
        }

        // Super admin thấy tất cả
        if ($user->role === 'super_admin') {
            return $query;
        }

        // Organization owner thấy chỉ tiêu của tổ chức mình
        if ($user->role === 'organization_owner') {
            $org = \App\Models\Organization::where('organization_owner_id', $user->id)->first();
            if ($org) {
                return $query->where('organization_id', $org->id);
            }
            return $query->whereNull('id');
        }

        // CTV thấy chỉ tiêu của tổ chức (read-only)
        if ($user->role === 'ctv') {
            $collaborator = \App\Models\Collaborator::where('email', $user->email)->first();
            if ($collaborator && $collaborator->organization) {
                return $query->where('organization_id', $collaborator->organization_id);
            }
            return $query->whereNull('id');
        }

        // Các role khác không thấy gì
        return $query->whereNull('id');
    }

    public static function getPages(): array {
        return [
            'index' => ListQuotas::route('/'),
            'create' => CreateQuota::route('/create'),
            'edit' => EditQuota::route('/{record}/edit'),
        ];
    }

    public static function getModalWidth(): string {
        return '6xl'; // Làm modal rộng hơn
    }
}
