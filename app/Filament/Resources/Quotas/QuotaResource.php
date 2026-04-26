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

    protected static string|BackedEnum|null $navigationIcon = null;
    protected static string|\UnitEnum|null $navigationGroup = 'Tuyển sinh';
    protected static ?string $navigationLabel = 'Đợt tuyển & Chỉ tiêu';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool {
        return false; // Thay bằng Chỉ tiêu năm (AnnualQuotaResource) và Đợt tuyển sinh (IntakeResource)
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
        $query = parent::getEloquentQuery()->with('intake');
        $user = \Illuminate\Support\Facades\Auth::user();

        if (!$user) {
            return $query->whereNull('id');
        }

        // Quản trị viên và CTV thấy tất cả chỉ tiêu dựa trên quyền
        if ($user->can('quota_view_any')) {
            return $query;
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

    public static function canViewAny(): bool {
        return \Illuminate\Support\Facades\Auth::user()?->can('quota_view_any') ?? false;
    }

    public static function canCreate(): bool {
        return \Illuminate\Support\Facades\Auth::user()?->can('quota_create') ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool {
        return \Illuminate\Support\Facades\Auth::user()?->can('quota_update') ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool {
        return \Illuminate\Support\Facades\Auth::user()?->can('quota_delete') ?? false;
    }

    public static function getModalWidth(): string {
        return '6xl'; // Làm modal rộng hơn
    }
}
