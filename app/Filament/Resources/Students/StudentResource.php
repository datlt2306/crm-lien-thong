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
use App\Models\Payment;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class StudentResource extends Resource {
    protected static ?string $model = Student::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Tuyển sinh';
    protected static ?string $navigationLabel = 'Học viên';
    protected static ?int $navigationSort = 1;

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
            $user = Auth::user();
            if (!$user) {
                return null;
            }

            $version = \Illuminate\Support\Facades\Cache::get('crm-cache-dash:version', 1);
            $cacheKey = sprintf('students_navigation_badge:%s:%s:%s', $version, $user->id, $user->role);

            return (string) Cache::remember($cacheKey, now()->addMinutes(2), function () use ($user) {
                // Super admin đếm tất cả
                if ($user->role === 'super_admin') {
                    return Student::count();
                }

                // CTV đếm học viên của mình
                if ($user->role === 'ctv') {
                    return Student::whereRelation('collaborator', 'email', $user->email)->count();
                }

                // Kế toán & cán bộ hồ sơ đếm học viên đã được CTV xác nhận nộp tiền hoặc đã bị hoàn trả
                if (
                    $user->role === 'accountant'
                    || $user->role === 'document'
                    || ($user->roles && $user->roles->contains('name', 'accountant'))
                ) {
                    return Student::whereRelation('payment', fn($q) => $q->whereIn('status', ['submitted', 'verified', 'reverted']))->count();
                }

                return 0;
            });
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeTooltip(): ?string {
        return 'Số lượng học viên';
    }

    public static function getEloquentQuery(): Builder {
        $user = Auth::user();
        
        // Nếu là super_admin, cho phép truy cập cả các bản ghi đã xóa (để Tabs/Filters hoạt động)
        if ($user?->role === 'super_admin') {
            return parent::getEloquentQuery()
                ->withoutGlobalScopes([
                    \Illuminate\Database\Eloquent\SoftDeletingScope::class,
                ])
                ->with(['payment', 'collaborator', 'intake']);
        }

        $query = parent::getEloquentQuery()->with(['payment', 'collaborator', 'intake']);

        if (!$user) {
            return $query;
        }


        // CTV thấy student của mình
        if ($user->role === 'ctv') {
            return $query->whereRelation('collaborator', 'email', $user->email);
        }

        // Kế toán & cán bộ hồ sơ thấy học viên đang nộp tiền hoặc đã xác minh hoặc đã hoàn trả
        if (
            $user->role === 'accountant'
            || $user->role === 'document'
            || ($user->roles && $user->roles->contains('name', 'accountant'))
        ) {
            return $query->whereRelation('payment', fn($q) => $q->whereIn('status', ['submitted', 'verified', 'reverted']));
        }

        // Fallback: không thấy gì
        return $query->whereNull('id');
    }

}
