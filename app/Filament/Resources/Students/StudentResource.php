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

    protected static string|\UnitEnum|null $navigationGroup = 'Quản lý dữ liệu';
    protected static ?string $navigationLabel = 'Học viên';
    protected static ?int $navigationSort = 2;

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
        $pages = [
            'index' => ListStudents::route('/'),
            'view' => ViewStudent::route('/{record}'),
        ];

        // Chỉ super_admin và chủ đơn vị mới có thể tạo và chỉnh sửa
        if (in_array(Auth::user()?->role, ['super_admin', 'chủ đơn vị'])) {
            $pages['create'] = CreateStudent::route('/create');
            $pages['edit'] = EditStudent::route('/{record}/edit');
        }

        return $pages;
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

        // CTV thấy student của mình và của downline trong nhánh
        if ($user->role === 'ctv') {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                // Lấy danh sách ID của tất cả downline trong nhánh
                $downlineIds = self::getDownlineIds($collaborator->id);

                // Thêm ID của chính mình vào danh sách
                $allCollaboratorIds = array_merge([$collaborator->id], $downlineIds);

                return $query->whereIn('collaborator_id', $allCollaboratorIds);
            }
        }

        // Fallback: không thấy gì
        return $query->whereNull('id');
    }

    /**
     * Lấy danh sách ID của tất cả downline trong nhánh
     */
    private static function getDownlineIds(int $collaboratorId): array {
        $downlineIds = [];

        // Lấy tất cả downline trực tiếp
        $directDownlines = Collaborator::where('upline_id', $collaboratorId)->get();

        foreach ($directDownlines as $downline) {
            $downlineIds[] = $downline->id;

            // Đệ quy lấy downline của downline
            $subDownlineIds = self::getDownlineIds($downline->id);
            $downlineIds = array_merge($downlineIds, $subDownlineIds);
        }

        return $downlineIds;
    }
}
