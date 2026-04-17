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

            // Super admin đếm tất cả
            if ($user->role === 'super_admin') {
                return (string) Student::count();
            }


            // CTV đếm học viên của mình và downline
            if ($user->role === 'ctv') {
                $collaborator = Collaborator::where('email', $user->email)->first();
                if ($collaborator) {
                    return (string) Student::where('collaborator_id', $collaborator->id)->count();
                }
            }

            // Kế toán & cán bộ hồ sơ đếm học viên đã được CTV xác nhận nộp tiền
            if (
                $user->role === 'accountant'
                || $user->role === 'document'
                || ($user->roles && $user->roles->contains('name', 'accountant'))
            ) {
                return (string) Student::whereHas('payment', function ($query) {
                    $query->whereIn('status', ['submitted', 'verified']);
                })->count();
            }

            return '0';
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeTooltip(): ?string {
        return 'Số lượng học viên';
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


        // CTV thấy student của mình
        if ($user->role === 'ctv') {
            $collaborator = Collaborator::where('email', $user->email)->first();
            if ($collaborator) {
                return $query->where('collaborator_id', $collaborator->id);
            }
        }

        // Kế toán & cán bộ hồ sơ chỉ thấy học viên đã được CTV xác nhận nộp tiền (để xác minh / xử lý hồ sơ)
        if (
            $user->role === 'accountant'
            || $user->role === 'document'
            || ($user->roles && $user->roles->contains('name', 'accountant'))
        ) {
            return $query->whereHas('payment', function ($paymentQuery) {
                $paymentQuery->whereIn('status', ['submitted', 'verified']);
            });
        }

        // Fallback: không thấy gì
        return $query->whereNull('id');
    }

}
