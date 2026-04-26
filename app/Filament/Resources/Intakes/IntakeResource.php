<?php

namespace App\Filament\Resources\Intakes;

use App\Filament\Resources\Intakes\Pages\CreateIntake;
use App\Filament\Resources\Intakes\Pages\EditIntake;
use App\Filament\Resources\Intakes\Pages\ListIntakes;
use App\Filament\Resources\Intakes\Schemas\IntakeForm;
use App\Filament\Resources\Intakes\Tables\IntakesTable;
use App\Models\Intake;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class IntakeResource extends Resource {
    protected static ?string $model = Intake::class;

    protected static string|BackedEnum|null $navigationIcon = null;
    protected static string|\UnitEnum|null $navigationGroup = 'Tuyển sinh';
    protected static ?string $navigationLabel = 'Đợt tuyển sinh';
    protected static ?int $navigationSort = 4;

    public static function shouldRegisterNavigation(): bool {
        return static::canViewAny();
    }

    public static function form(Schema $schema): Schema {
        return IntakeForm::configure($schema);
    }

    public static function table(Table $table): Table {
        return IntakesTable::configure($table);
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

        // Theo ma trận phân quyền, tất cả những người có quyền xem đều thấy toàn bộ đợt tuyển sinh
        if ($user->can('intake_view_any')) {
            return $query;
        }

        // Các role khác không thấy gì
        return $query->whereNull('id');
    }

    public static function canViewAny(): bool {
        return \Illuminate\Support\Facades\Auth::user()?->can('intake_view_any') ?? false;
    }

    public static function canCreate(): bool {
        return \Illuminate\Support\Facades\Auth::user()?->can('intake_create') ?? false;
    }

    public static function canEdit(\Illuminate\Database\Eloquent\Model $record): bool {
        return \Illuminate\Support\Facades\Auth::user()?->can('intake_update') ?? false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool {
        return \Illuminate\Support\Facades\Auth::user()?->can('intake_delete') ?? false;
    }

    public static function getPages(): array {
        return [
            'index' => ListIntakes::route('/'),
            'create' => CreateIntake::route('/create'),
            'edit' => EditIntake::route('/{record}/edit'),
        ];
    }
}
