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
        return $user && in_array($user->role, ['super_admin', 'organization_owner', 'ctv']);
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

        // Super admin thấy tất cả
        if ($user->role === 'super_admin') {
            return $query;
        }

        // Organization owner thấy đợt tuyển sinh của tổ chức mình
        if ($user->role === 'organization_owner') {
            $org = \App\Models\Organization::where('organization_owner_id', $user->id)->first();
            if ($org) {
                return $query->where('organization_id', $org->id);
            }
            return $query->whereNull('id');
        }

        // CTV thấy đợt tuyển sinh của tổ chức (read-only)
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
            'index' => ListIntakes::route('/'),
            'create' => CreateIntake::route('/create'),
            'edit' => EditIntake::route('/{record}/edit'),
        ];
    }
}
