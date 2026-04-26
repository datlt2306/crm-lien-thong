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

    protected static string|BackedEnum|null $navigationIcon = null;

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


    public static function getEloquentQuery(): Builder {
        $user = Auth::user();
        
        $query = static::getModel()::query()
            ->withTrashed() // Cho phép truy vấn cả bản ghi đã xóa (cần thiết cho Tab Thùng rác)
            ->with(['payment', 'collaborator', 'intake']);

        if (!$user) {
            return $query->whereNull('students.id');
        }

        // 1. Quyền xem tất cả (Admin, Super Admin)
        if ($user->can('student_view_any') && in_array($user->role, ['super_admin', 'admin'])) {
            return $query;
        }

        // 2. CTV: Chỉ thấy sinh viên của mình (bao gồm cả Inactive và Trashed)
        if ($user->role === 'collaborator') {
            return $query->whereRelation('collaborator', 'email', $user->email);
        }

        // 3. Nhân sự văn phòng: Nếu có quyền xem danh sách thì thấy tất cả (vì đã có Tab lọc Active/Inactive/Trash)
        if ($user->can('student_view_any')) {
            return $query;
        }

        // Mặc định không thấy gì
        return $query->whereNull('students.id');
    }
}
