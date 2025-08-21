<?php

namespace App\Filament\Resources\Students;

use App\Filament\Resources\Students\Pages\CreateStudent;
use App\Filament\Resources\Students\Pages\EditStudent;
use App\Filament\Resources\Students\Pages\ListStudents;
use App\Filament\Resources\Students\Pages\ViewStudent;
use App\Filament\Resources\Students\Schemas\StudentForm;
use App\Filament\Resources\Students\Schemas\StudentInfolist;
use App\Filament\Resources\Students\Tables\StudentsTable;
use App\Models\Student;
use App\Models\Collaborator;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class StudentResource extends Resource {
    protected static ?string $model = Student::class;

    protected static ?string $navigationLabel = 'Học viên';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public static function form(Schema $schema): Schema {
        return StudentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema {
        return StudentInfolist::configure($schema);
    }

    public static function table(Table $table): Table {
        return StudentsTable::configure($table);
    }

    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getPages(): array {
        return [
            'index' => ListStudents::route('/'),
            'create' => CreateStudent::route('/create'),
            'view' => ViewStudent::route('/{record}'),
            'edit' => EditStudent::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string {
        try {
            return (string) Student::count();
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeTooltip(): ?string {
        return 'The number of students';
    }

    public static function getEloquentQuery(): Builder {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (!$user) {
            return $query;
        }

        // Super admin thấy tất cả
        if ($user->role === 'super_admin') {
            return $query;
        }

        // Chủ đơn vị thấy student của tổ chức mình
        if ($user->role === 'chủ đơn vị') {
            $org = \App\Models\Organization::where('owner_id', $user->id)->first();
            if ($org) {
                return $query->where('organization_id', $org->id);
            }
        }

        // CTV chỉ thấy student của mình
        if ($user->role === 'ctv') {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                return $query->where('collaborator_id', $collaborator->id);
            }
        }

        // Fallback: không thấy gì
        return $query->whereNull('id');
    }
}
