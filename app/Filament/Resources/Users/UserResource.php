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

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

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

        // Super admin thấy tất cả user
        if ($user->role === 'super_admin') {
            return $query;
        }

        // Owner chỉ thấy user trong tổ chức của mình
        if ($user->role === 'organization_owner') {
            $org = \App\Models\Organization::where('organization_owner_id', $user->id)->first();
            if ($org) {
                // Thấy tất cả user thuộc tổ chức (dựa trên users.organization_id)
                return $query->where('organization_id', $org->id);
            }

            // Nếu không có tổ chức, chỉ thấy chính mình
            return $query->where('id', $user->id);
        }

        // Mặc định: không thấy gì
        return $query->whereNull('id');
    }

    public static function getNavigationBadge(): ?string {
        try {
            $user = Auth::user();
            if (!$user) return null;

            if ($user->role === 'super_admin') {
                return (string) User::count();
            }

            if ($user->role === 'organization_owner') {
                $org = \App\Models\Organization::where('organization_owner_id', $user->id)->first();
                if ($org) {
                    // Đếm user trong tổ chức (dựa trên users.organization_id)
                    return (string) User::where('organization_id', $org->id)->count();
                }
                return '1'; // Chỉ có chính mình
            }

            return null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function getNavigationBadgeTooltip(): ?string {
        return 'The number of users';
    }
}
