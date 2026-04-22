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

            return (string) Cache::remember($cacheKey, now()->addMinutes(10), function () use ($user) {
                $query = Student::query();

                // Nhóm Admin & Super Admin đếm tất cả (cả active và inactive)
                if (in_array($user->role, ['super_admin', 'admin'])) {
                    return $query->count();
                }

                // Nhóm Cán bộ văn phòng (Kế toán, Hồ sơ, Tuyển sinh): Chỉ đếm học viên ĐANG HOẠT ĐỘNG
                if (in_array($user->role, ['organization_owner', 'admissions', 'document', 'accountant']) || 
                    (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['organization_owner', 'admissions', 'document', 'accountant']))) {
                    return $query->where('is_active', true)->count();
                }

                // CTV đếm học viên của mình
                if ($user->role === 'ctv') {
                    return $query->whereRelation('collaborator', 'email', $user->email)->count();
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
        
        $query = static::getModel()::query()
            ->with(['payment', 'collaborator', 'intake']);

        if (!$user) {
            return $query->whereNull('students.id');
        }

        // Nhóm Admin (Thấy tất cả)
        if (in_array($user->role, ['super_admin', 'admin'])) {
            return $query;
        }

        // Nhóm Cán bộ văn phòng (Kế toán, Hồ sơ, Tuyển sinh): Chỉ thấy học viên ĐANG HOẠT ĐỘNG
        if (in_array($user->role, ['organization_owner', 'admissions', 'document', 'accountant']) || 
            (method_exists($user, 'hasAnyRole') && $user->hasAnyRole(['organization_owner', 'admissions', 'document', 'accountant']))) {
            return $query->where('is_active', true);
        }

        // CTV thấy sinh viên của mình (bao gồm cả Inactive để họ có thể kích hoạt lại nếu muốn)
        if ($user->role === 'ctv') {
            return $query->whereRelation('collaborator', 'email', $user->email);
        }

        // Mặc định không thấy gì
        return $query->whereNull('students.id');
    }
}
