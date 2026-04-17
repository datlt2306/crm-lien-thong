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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;
    protected static string|\UnitEnum|null $navigationGroup = 'Tuyển sinh';
    protected static ?string $navigationLabel = 'Đợt tuyển sinh';
    protected static ?int $navigationSort = 3; // Trước "Đợt tuyển & Chỉ tiêu" (4): tạo đợt (tên + khoảng thời gian) trước, gán chỉ tiêu sau

    public static function shouldRegisterNavigation(): bool {
        $user = \Illuminate\Support\Facades\Auth::user();
        return $user && in_array($user->role, ['super_admin', 'ctv', 'admissions', 'accountant', 'document']);
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

        // Sau khi bỏ đơn vị, tất cả các vai trò quản lý đều thấy toàn bộ đợt tuyển sinh
        if (in_array($user->role, ['super_admin', 'admissions', 'accountant', 'document'])) {
            return $query;
        }

        // CTV thấy toàn bộ đợt tuyển sinh (read-only)
        if ($user->role === 'ctv') {
            return $query;
        }

        // Các role khác không thấy gì
        return $query->whereNull('id');
    }

    public static function getPages(): array {
        return [
            'index' => ListIntakes::route('/'),
            'create' => CreateIntake::route('/create'),
            'edit' => EditIntake::route('/{record}/edit'),
        ];
    }
}
