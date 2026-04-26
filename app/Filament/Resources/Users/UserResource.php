<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\CreateUser;
use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;

use App\Filament\Resources\Users\Schemas\UserForm;
use App\Filament\Resources\Users\Schemas\UserInfolist;
use App\Filament\Resources\Users\Tables\UsersTable;
use App\Models\User;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class UserResource extends Resource {
    protected static ?string $model = User::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Hệ thống';
    protected static ?string $navigationLabel = 'Người dùng';
    protected static ?int $navigationSort = 1;

    protected static string|BackedEnum|null $navigationIcon = null;

    public static function form(Schema $schema): Schema {
        return UserForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema {
        return UserInfolist::configure($schema);
    }

    public static function table(Table $table): Table {
        return UsersTable::configure($table);
    }

    public static function getRelations(): array {
        return [
            //
        ];
    }

    public static function getPages(): array {
        return [
            'index' => ListUsers::route('/'),
            'create' => CreateUser::route('/create'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }

    public static function shouldRegisterNavigation(): bool {
        return Gate::allows('viewAny', User::class);
    }

    public static function getEloquentQuery(): Builder {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (!$user) {
            return $query->whereNull('id');
        }

        // Cho phép xem nếu có quyền
        if ($user->can('user_view_any')) {
            // Nếu không phải super_admin thì ẩn các tài khoản super_admin
            if ($user->role !== 'super_admin') {
                return $query->where('role', '!=', 'super_admin');
            }
            return $query;
        }

        // Mặc định: không thấy gì
        return $query->whereNull('id');
    }

}
